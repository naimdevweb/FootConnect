<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\PhotoRepository;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;

class PhotoService
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatutRepository $statutRepository
    ) {}
    
    /**
     * Récupère les photos filtrées selon l'utilisateur et le type de filtre
     * 
     * @param User|null $currentUser L'utilisateur connecté
     * @param string|null $filterType Type de filtre (following, all, etc.)
     * @return array Photos filtrées
     */
    public function getFilteredPhotos(?User $currentUser, ?string $filterType = null): array
    {
        try {
            // Si l'utilisateur est connecté et demande uniquement les abonnements
            if ($currentUser instanceof User && $filterType === 'following') {
                // Récupérer les photos des utilisateurs que l'utilisateur actuel suit
                $statuts = $this->statutRepository->findBy([
                    'user' => $currentUser,
                    'isFollowing' => true
                ]);
                
                if (empty($statuts)) {
                    return [];
                }
                
                // Extraire les IDs des utilisateurs suivis
                $followedUserIds = array_map(function($statut) {
                    return $statut->getOtherUser()->getId();
                }, $statuts);
                
                // Récupérer les photos des utilisateurs suivis
                return $this->photoRepository->findBy(
                    ['user' => $followedUserIds],
                    ['createdAt' => 'DESC']
                );
            }
            
            // Comportement par défaut: filtrer les utilisateurs bloqués
            return $currentUser instanceof User
                ? $this->photoRepository->findPhotosWithoutBlockedUsers($currentUser)
                : $this->photoRepository->findBy([], ['createdAt' => 'DESC']);
        } catch (\Exception $e) {
            // Gérer l'erreur de manière silencieuse et retourner un tableau vide
            return [];
        }
    }
    
    /**
     * Récupère les statuts entre l'utilisateur courant et les propriétaires des photos
     */
    public function getUserStatuts(array $photos, $currentUser): array
    {
        $userStatuts = [];

        foreach ($photos as $photo) {
            $isLiked = false;
            $isFavorite = false;

            if ($currentUser) {
                $isLiked = $photo->getLikes()->exists(function ($key, $like) use ($currentUser) {
                    return $like->getUser() === $currentUser;
                });

                $isFavorite = $currentUser->getFavoritePosts()->exists(function ($key, $favoritePost) use ($photo) {
                    return $favoritePost->getPhoto() === $photo;
                });
            }

            $userStatuts[$photo->getId()] = [
                'isLiked' => $isLiked,
                'isFavorite' => $isFavorite,
            ];
        }

        return $userStatuts;
    }
}