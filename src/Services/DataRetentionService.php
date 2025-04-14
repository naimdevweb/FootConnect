<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\Photo;
use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;

class DataRetentionService
{
    private $retentionPolicies = [
        'inactive_accounts' => '2 years',    // Durée avant suppression des comptes inactifs
        'deleted_accounts' => '30 days',     // Période de grâce avant suppression définitive des données
        'user_photos' => '5 years',          // Durée de conservation des photos
        'comments' => '5 years',             // Durée de conservation des commentaires
        'logs' => '1 year',                  // Durée de conservation des logs
        'messages' => '3 years',             // Durée de conservation des messages privés
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère les règles de conservation des données
     * @return array Les règles de conservation des données
     */
    public function getRetentionPolicies(): array
    {
        return $this->retentionPolicies;
    }

    /**
     * Anonymise les données d'un utilisateur supprimé
     * @param User $user L'utilisateur à anonymiser
     */
    public function anonymizeUserData(User $user): void
    {
        // Conserver l'ID mais anonymiser les données personnelles
        $user->setEmail('anonymized_' . md5($user->getEmail() . microtime()) . '@anonymized.com');
        $user->setPseudo('Utilisateur anonymisé');
        $user->setIsDeleted(true);
        $user->setDeletedAt(new \DateTime());
        
        $this->entityManager->flush();
    }

    /**
     * Nettoie les comptes inactifs selon la politique de rétention
     * @return int Le nombre de comptes anonymisés
     */
    public function cleanInactiveAccounts(): int
    {
        $inactivePeriod = $this->retentionPolicies['inactive_accounts'];
        $dateThreshold = new \DateTime();
        $dateThreshold->modify('-' . $inactivePeriod);

        $inactiveUsers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.lastLoginAt < :threshold')
            ->andWhere('u.isDeleted = :isDeleted')
            ->setParameter('threshold', $dateThreshold)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($inactiveUsers as $user) {
            $this->anonymizeUserData($user);
            $count++;
        }

        return $count;
    }

    /**
     * Supprime définitivement les données des comptes supprimés après la période de grâce
     * @return int Le nombre de comptes supprimés
     */
    public function purgeDeletedAccounts(): int
    {
        $gracePeriod = $this->retentionPolicies['deleted_accounts'];
        $dateThreshold = new \DateTime();
        $dateThreshold->modify('-' . $gracePeriod);

        $deletedUsers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.deletedAt < :threshold')
            ->andWhere('u.isDeleted = :isDeleted')
            ->setParameter('threshold', $dateThreshold)
            ->setParameter('isDeleted', true)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($deletedUsers as $user) {
            $this->entityManager->remove($user);
            $count++;
        }
        
        $this->entityManager->flush();
        return $count;
    }
}