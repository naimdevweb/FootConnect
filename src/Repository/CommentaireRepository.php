<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }
    // Ajoutez ces méthodes à votre repository existant

/**
 * Vérifie si un commentaire est liké par un utilisateur
 */
public function isLikedByUser(Commentaire $commentaire, User $user): bool
{
    $conn = $this->getEntityManager()->getConnection();
    
    $sql = '
        SELECT COUNT(1) as liked
        FROM like_commentaire
        WHERE commentaire_id = :commentId
        AND user_id = :userId
    ';
    
    $result = $conn->executeQuery($sql, [
        'commentId' => $commentaire->getId(),
        'userId' => $user->getId(),
    ]);
    
    return (bool) $result->fetchOne();
}

/**
 * Ajoute un like à un commentaire
 */
public function addLike(Commentaire $commentaire, User $user): void
{
    if (!$this->isLikedByUser($commentaire, $user)) {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            INSERT INTO like_commentaire (commentaire_id, user_id)
            VALUES (:commentId, :userId)
        ';
        
        $conn->executeStatement($sql, [
            'commentId' => $commentaire->getId(),
            'userId' => $user->getId(),
        ]);
    }
}

/**
 * Supprime un like d'un commentaire
 */
public function removeLike(Commentaire $commentaire, User $user): void
{
    $conn = $this->getEntityManager()->getConnection();
    
    $sql = '
        DELETE FROM like_commentaire
        WHERE commentaire_id = :commentId
        AND user_id = :userId
    ';
    
    $conn->executeStatement($sql, [
        'commentId' => $commentaire->getId(),
        'userId' => $user->getId(),
    ]);
}

/**
 * Récupère le nombre de likes d'un commentaire
 */
public function getLikesCount(Commentaire $commentaire): int
{
    $conn = $this->getEntityManager()->getConnection();
    
    $sql = '
        SELECT COUNT(1) as count
        FROM like_commentaire
        WHERE commentaire_id = :commentId
    ';
    
    $result = $conn->executeQuery($sql, [
        'commentId' => $commentaire->getId(),
    ]);
    
    return (int) $result->fetchOne();
}

    //    /**
    //     * @return Commentaire[] Returns an array of Commentaire objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commentaire
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
