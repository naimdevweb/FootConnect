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
     * Récupère les photos filtrées selon l'utilisateur
     */
    public function getFilteredPhotos(?User $currentUser): array
    {
        return $currentUser instanceof User
            ? $this->photoRepository->findPhotosWithoutBlockedUsers($currentUser)
            : $this->photoRepository->findBy([], ['createdAt' => 'DESC']);
    }
    
    /**
     * Récupère les statuts entre l'utilisateur courant et les propriétaires des photos
     */
    public function getUserStatuts(array $photos, ?User $currentUser): array
    {
        if (!$currentUser) {
            return [];
        }

        foreach ($photos as $photo) {
            $photoUserId = $photo->getUser()->getId();
            $userStatuts[$photoUserId] = $this->statutRepository->findOneBy([
                'user' => $currentUser,
                'otherUser' => $photo->getUser()
            ]);
        }

        return $userStatuts;
    }
}