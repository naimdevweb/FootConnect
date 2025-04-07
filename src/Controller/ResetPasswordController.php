<?php
// filepath: c:\Users\Dev404\Documents\foot_connect\FOOT_CONNECT\src\Controller\ResetPasswordController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Services\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * Contrôleur pour la réinitialisation de mot de passe
 */
class ResetPasswordController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private TokenGeneratorInterface $tokenGenerator;
    private EmailService $emailService;

    /**
     * Constructeur du contrôleur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->tokenGenerator = $tokenGenerator;
        $this->emailService = $emailService;
    }

    /**
     * Affiche le formulaire de demande de réinitialisation
     */
    #[Route('/reset-password', name: 'app_reset_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processResetPasswordRequest($form->get('email')->getData());
        }

        return $this->render('security/reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de confirmation après envoi d'email
     */
    #[Route('/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Cette page est présentée après qu'un utilisateur a demandé la réinitialisation de son mot de passe
        // Cette page empêche les attaques par force brute en affichant toujours le même message
        
        return $this->render('security/reset_password/check_email.html.twig');
    }

    /**
     * Page de réinitialisation du mot de passe
     */
    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        string $token,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Valider le token
        $user = $this->userRepository->findOneBy([
            'resetToken' => $token,
        ]);

        if (!$user) {
            return $this->render('security/reset_password/error.html.twig', [
                'error' => 'Le lien de réinitialisation est invalide ou a expiré.'
            ]);
        }

        // Vérifier si le token n'a pas expiré (24 heures)
        $tokenCreatedAt = $user->getResetTokenCreatedAt();
        if (!$tokenCreatedAt || $tokenCreatedAt->modify('+24 hours') < new \DateTime()) {
            return $this->render('security/reset_password/error.html.twig', [
                'error' => 'Le lien de réinitialisation a expiré. Veuillez faire une nouvelle demande.'
            ]);
        }

        // Formulaire pour saisir le nouveau mot de passe
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Réinitialiser le token
            $user->setResetToken(null);
            $user->setResetTokenCreatedAt(null);
            
            // Définir le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * Traite la demande de réinitialisation de mot de passe
     */
    private function processResetPasswordRequest(string $email): Response
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        // Ne pas révéler si l'utilisateur existe ou non
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        // Générer un token unique
        $token = $this->tokenGenerator->generateToken();
        $user->setResetToken($token);
        $user->setResetTokenCreatedAt(new \DateTime());
        
        $this->entityManager->flush();

        // Envoyer l'email
        try {
            $this->sendResetPasswordEmail($user, $token);
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'Une erreur s\'est produite lors de l\'envoi de l\'email. Veuillez réessayer.');
            return $this->redirectToRoute('app_reset_password_request');
        }

        return $this->redirectToRoute('app_check_email');
    }

    /**
     * Envoie l'email de réinitialisation du mot de passe
     */
    private function sendResetPasswordEmail(User $user, string $token): void
    {
        // Générer une URL absolue avec le domaine complet
        $resetLink = $this->generateUrl(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->emailService->sendEmail(
            $user->getEmail(),
            'Réinitialisation de votre mot de passe FootConnect',
            'security/reset_password/email.html.twig',
            [
                'resetToken' => $token,
                'resetLink' => $resetLink,
                'tokenLifetime' => 24, // heures
                'username' => $user->getPseudo(),
                'user' => $user,
            ]
        );
    }
}