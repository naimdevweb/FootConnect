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


    

public function findPopularPhotos(int $limit = 10): array
{
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.likes', 'l')
        ->groupBy('p.id')
        ->orderBy('COUNT(l.id)', 'DESC')
        ->setMaxResults($limit);
        
    return $qb->getQuery()->getResult();
}

// Dans UserRepository.php
public function findActiveUsers(int $limit = 5): array
{
    $qb = $this->createQueryBuilder('u')
        ->leftJoin('u.photos', 'p')
        ->groupBy('u.id')
        ->orderBy('COUNT(p.id)', 'DESC')
        ->setMaxResults($limit);
        
    return $qb->getQuery()->getResult();
}


/**
 * Récupère les photos des utilisateurs suivis par l'utilisateur courant
 * 
 * @param User $user L'utilisateur connecté
 * @return array<Photo> Photos des utilisateurs suivis
 */
public function findByFollowedUsers(User $user): array
{
    try {
        // Requête pour trouver les photos des utilisateurs que l'utilisateur actuel suit
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->join('App\Entity\Statut', 's', 'WITH', 's.user = :currentUser AND s.otherUser = u AND s.isFollowing = 1')
            ->setParameter('currentUser', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    } catch (\Exception $e) {
        return [];
    }
}

 /**
     * Récupère toutes les photos triées par date
     */
    public function findAllPhotosOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

 /**
     * Récupère les photos de la communauté (tous sauf admin/modérateurs)
     */
    public function findCommunityPhotos(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->andWhere('u.roles NOT LIKE :modRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->setParameter('modRole', '%ROLE_MODERATOR%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }




}