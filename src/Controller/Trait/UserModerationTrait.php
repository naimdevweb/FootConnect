<?php

namespace App\Controller\Trait;

use App\Entity\User;
use App\Form\UserBanType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait de gestion des utilisateurs pour la modération
 */
trait UserModerationTrait
{
    /**
     * Permet de bannir ou débannir un utilisateur
     */
    public function banUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $form = $this->createForm(UserBanType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->toggleUserBanStatus($user, $form->get('banReason')->getData(), $entityManager);
                return $this->redirectToRoute('app_moderation_users');
            }

            return $this->render('moderation/ban_user.html.twig', [
                'user' => $user,
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'gestion du bannissement', 'app_moderation_dashboard');
        }
    }

    /**
     * Liste tous les utilisateurs avec options de modération
     */
    public function listUsers(UserRepository $userRepository): Response
    {
        try {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

            return $this->render('moderation/users.html.twig', [
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'chargement des utilisateurs', 'app_moderation_dashboard');
        }
    }

    /**
     * Modifie le statut de bannissement d'un utilisateur
     */
    private function toggleUserBanStatus(User $user, ?string $banReason, EntityManagerInterface $entityManager): void
    {
        $currentStatus = $user->isBanned();
        $user->setBanned(!$currentStatus);

        if (!$currentStatus) {
            $user->setBanReason($banReason);
            $user->setBannedAt(new \DateTime());
            $this->addFlash('success', 'L\'utilisateur ' . $user->getPseudo() . ' a été banni.');
        } else {
            $user->setBanReason(null);
            $user->setBannedAt(null);
            $this->addFlash('success', 'L\'utilisateur ' . $user->getPseudo() . ' a été débanni.');
        }

        $entityManager->flush();
    }
}