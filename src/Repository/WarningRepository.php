<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Repository/WarningRepository.php

namespace App\Repository;

use App\Entity\Warning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Warning
 *
 * @extends ServiceEntityRepository<Warning>
 */
class WarningRepository extends ServiceEntityRepository
{
    /**
     * Constructeur avec injection de dépendances
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warning::class);
    }

    /**
     * Récupère les avertissements non lus pour un utilisateur
     */
    public function findUnviewedByUser(int $userId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :userId')
            ->andWhere('w.viewed = :viewed')
            ->setParameter('userId', $userId)
            ->setParameter('viewed', false)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}