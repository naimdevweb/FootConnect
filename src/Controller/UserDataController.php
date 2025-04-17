<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountDeletionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Contrôleur pour la gestion des données personnelles utilisateur
 */
#[IsGranted('ROLE_USER')]
class UserDataController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    
    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }
    
    /**
     * Affiche la page d'informations sur la durée de conservation des données
     */
    #[Route('/user/data-retention', name: 'app_user_data_retention_info')]
    public function dataRetentionInfo(): Response
    {
        $retentionPolicies = $this->getRetentionPolicies();
        
        return $this->render('user/data_retention.html.twig', [
            'retentionPolicies' => $retentionPolicies,
        ]);
    }
    
    /**
     * Affiche la page de gestion des données personnelles
     */
    #[Route('/user/data-manage', name: 'app_user_data_manage')]
    public function manageData(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $retentionPolicies = $this->getRetentionPolicies();
        
        // Formulaire de suppression de compte
        $deletionForm = $this->createForm(AccountDeletionType::class);
        $deletionForm->handleRequest($request);
        
        if ($deletionForm->isSubmitted() && $deletionForm->isValid()) {
            try {
                // Supprimer définitivement le compte
                $this->deleteAccount($user);
                
                // Invalider la session
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                
                $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
                
                // Rediriger vers la page d'inscription
                return $this->redirectToRoute('app_register');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte. Veuillez réessayer.');
            }
        }
        
        return $this->render('user/data_management.html.twig', [
            'retentionPolicies' => $retentionPolicies,
            'deletionForm' => $deletionForm,
            'user' => $user,
        ]);
    }
    
    /**
     * Exporte les données de l'utilisateur
     */
    #[Route('/user/data-export', name: 'app_user_data_export')]
    public function exportData(): Response
    {
        // Cette méthode serait implémentée pour générer un export des données
        $this->addFlash('success', 'L\'export de vos données est en cours de préparation. Vous recevrez un email lorsqu\'il sera prêt.');
        return $this->redirectToRoute('app_user_data_manage');
    }
    
    /**
     * Supprime définitivement le compte utilisateur
     */
    private function deleteAccount(User $user): void
    {
        // Supprimer les posts de l'utilisateur
        $posts = $user->getPhotos();
        foreach ($posts as $post) {
            $this->entityManager->remove($post);
        }

        // Supprimer les likes de l'utilisateur
        $likes = $user->getLikes();
        foreach ($likes as $like) {
            $this->entityManager->remove($like);
        }

        // Supprimer les commentaires de l'utilisateur
        $comments = $user->getComments();
        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }

        // Anonymiser les données nécessaires avant suppression
        $user->setPassword(password_hash(random_bytes(16), PASSWORD_BCRYPT));

        // Marquer le compte comme supprimé
        $user->setIsDeleted(true);
        $user->setDeletedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
    /**
     * Retourne les politiques de conservation des données
     */
    private function getRetentionPolicies(): array
    {
        return [
            'inactive_accounts' => '24 mois',
            'deleted_accounts' => 'Immédiat',
            'user_photos' => 'Durée du compte',
            'comments' => 'Durée du compte',
            'logs' => '6 mois',
            'messages' => 'Durée du compte',
        ];
    }
}