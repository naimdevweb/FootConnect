<?php

namespace App\Controller\Trait;

use App\Entity\Statut;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait de gestion des relations d'abonnement
 */
trait ProfileFollowTrait
{
    /**
     * Prépare les données pour la vue des abonnés
     */
    public function prepareFollowersData(User $user, EntityManagerInterface $entityManager): array
    {
        try {
            // Récupérer tous les abonnés (statuts où otherUser est l'utilisateur et isFollowing=1)
            $followers = $entityManager->getRepository(Statut::class)->findBy([
                'otherUser' => $user,
                'isFollowing' => 1
            ]);
            
            // Extraire les objets User (ceux qui suivent l'utilisateur)
            $followerUsers = [];
            foreach ($followers as $statut) {
                if (method_exists($statut, 'getUser') && $statut->getUser() instanceof User) {
                    $followerUsers[] = $statut->getUser();
                }
            }
            
            // Récupérer les statuts de l'utilisateur courant pour vérifier qui il suit
            $followingStatus = $entityManager->getRepository(Statut::class)->findBy([
                'user' => $this->getUser(),
                'isFollowing' => 1
            ]);
            
            return [
                'users' => $followerUsers,
                'isFollowers' => true,
                'followingStatus' => $followingStatus
            ];
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la récupération des abonnés: ' . $e->getMessage());
            
            if ($this->getParameter('kernel.environment') === 'dev') {
                throw $e;
            }
            
            return [
                'users' => [],
                'isFollowers' => true,
                'followingStatus' => []
            ];
        }
    }

    /**
     * Prépare les données pour la vue des abonnements
     */
    public function prepareFollowingData(User $user, EntityManagerInterface $entityManager): array
    {
        try {
            // Récupérer tous les utilisateurs suivis
            $following = $entityManager->getRepository(Statut::class)->findBy([
                'user' => $user,
                'isFollowing' => 1
            ]);
            
            $followingUsers = [];
            foreach ($following as $statut) {
                if (method_exists($statut, 'getOtherUser') && $statut->getOtherUser() instanceof User) {
                    $followingUsers[] = $statut->getOtherUser();
                }
            }
            
            // Récupérer les statuts de l'utilisateur courant pour vérifier qui il suit
            $followingStatus = $entityManager->getRepository(Statut::class)->findBy([
                'user' => $this->getUser(),
                'isFollowing' => 1
            ]);
            
            return [
                'users' => $followingUsers,
                'isFollowers' => false,
                'followingStatus' => $followingStatus
            ];
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            
            if ($this->getParameter('kernel.environment') === 'dev') {
                throw $e;
            }
            
            return [
                'users' => [],
                'isFollowers' => false,
                'followingStatus' => []
            ];
        }
    }
}