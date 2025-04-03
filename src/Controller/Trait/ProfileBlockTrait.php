<?php

namespace App\Controller\Trait;

use App\Entity\Statut;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait de gestion des utilisateurs bloqués
 */
trait ProfileBlockTrait
{
    /**
     * Prépare les données pour la vue des utilisateurs bloqués
     */
    public function prepareBlockedUsersData(User $user, EntityManagerInterface $entityManager): array
    {
        try {
            // Récupérer tous les utilisateurs bloqués
            $blocked = $entityManager->getRepository(Statut::class)->findBy([
                'user' => $user,
                'isBlocked' => 1
            ]);
            
            $blockedUsers = [];
            foreach ($blocked as $statut) {
                if (method_exists($statut, 'getOtherUser') && $statut->getOtherUser() instanceof User) {
                    $blockedUsers[] = $statut->getOtherUser();
                }
            }
            
            return [
                'users' => $blockedUsers,
                'mode' => 'blocked'
            ];
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            
            if ($this->getParameter('kernel.environment') === 'dev') {
                throw $e;
            }
            
            return [
                'users' => [],
                'mode' => 'blocked'
            ];
        }
    }
}