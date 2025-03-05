<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Repository/PhotoRepository.php

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * Récupère les photos en excluant celles des utilisateurs bloqués ou qui ont bloqué l'utilisateur courant
     * 
     * @param User $currentUser L'utilisateur connecté
     * @return array<Photo> Photos filtrées sans celles des utilisateurs bloqués
     */
    public function findPhotosWithoutBlockedUsers(User $currentUser): array
    {
        try {
            $qb = $this->createQueryBuilder('p')
                ->innerJoin('p.user', 'u')
                ->leftJoin('App\Entity\Statut', 's1', 'WITH', 's1.user = :currentUser AND s1.otherUser = u AND s1.isBlocked = 1')
                ->leftJoin('App\Entity\Statut', 's2', 'WITH', 's2.user = u AND s2.otherUser = :currentUser AND s2.isBlocked = 1')
                ->where('s1.id IS NULL OR s1.isBlocked = 0')
                ->andWhere('s2.id IS NULL OR s2.isBlocked = 0')
                ->setParameter('currentUser', $currentUser)
                ->orderBy('p.createdAt', 'DESC');
                
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}