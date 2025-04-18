<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/comment/delete/{id}', name: 'app_comment_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Commentaire $commentaire, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification que l'utilisateur actuel est l'auteur du commentaire
        if ($this->getUser() !== $commentaire->getUser()) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/comment/like/{id}', name: 'app_comment_like', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(Commentaire $commentaire, EntityManagerInterface $entityManager, CommentaireRepository $commentaireRepository): JsonResponse
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà liké ce commentaire
        $isLiked = $commentaireRepository->isLikedByUser($commentaire, $user);
        
        if ($isLiked) {
            // Supprimer le like
            $commentaireRepository->removeLike($commentaire, $user);
        } else {
            // Ajouter le like
            $commentaireRepository->addLike($commentaire, $user);
        }
        
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'isLiked' => !$isLiked]);
    }
}