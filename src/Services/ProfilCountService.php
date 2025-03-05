<?php
namespace App\Services;

use App\Entity\User;
use App\Interfaces\ProfilCountInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Statut;

class ProfilCountService implements ProfilCountInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function countFollowers(User $user): int
    {
        return $this->entityManager->getRepository(Statut::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.otherUser = :user')
            ->andWhere('s.isFollowing = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFollowing(User $user): int
    {
        return $this->entityManager->getRepository(Statut::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.isFollowing = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}