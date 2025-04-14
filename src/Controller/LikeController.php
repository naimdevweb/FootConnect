<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/LikeController.php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LikeController extends AbstractController
{
    #[Route('/like/{id}', name: 'app_like', methods: ['POST'])]
    public function toggleLike(EntityManagerInterface $entityManager, int $id): Response
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Vous devez être connecté pour liker une photo'], Response::HTTP_UNAUTHORIZED);
            }

            $photo = $entityManager->getRepository(Photo::class)->find($id);

            if (!$photo) {
                return $this->json(['error' => 'Photo non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $existingLike = $entityManager->getRepository(Like::class)->findOneBy([
                'user' => $user,
                'photo' => $photo
            ]);

            if ($existingLike) {
                $entityManager->remove($existingLike);
                $entityManager->flush();
                return $this->json(['message' => 'Like retiré', 'likesCount' => count($photo->getLikes())]);
            } else {
                $like = new Like();
                $like->setUser($user);
                $like->setPhoto($photo);
                $entityManager->persist($like);
                $entityManager->flush();
                return $this->json(['message' => 'Like ajouté', 'likesCount' => count($photo->getLikes())]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}