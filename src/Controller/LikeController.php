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
    #[Route('/like/{id}', name: 'app_like', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, int $id): Response
    {
        try {
            $user = $this->getUser();

            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour liker une photo');
                return $this->redirectToRoute('app_login');
            }

            $photo = $entityManager->getRepository(Photo::class)->find($id);
            
            if (!$photo) {
                $this->addFlash('error', 'Photo non trouvée');
                return $this->redirectToRoute('app_home');
            }
            
            // Vérifier si l'utilisateur a déjà liké cette photo
            $existingLike = $entityManager->getRepository(Like::class)->findOneBy([
                'user' => $user,
                'photo' => $photo
            ]);
            
            if (!$existingLike) {
                $like = new Like();
                $like->setUser($user);
                $like->setPhoto($photo);
                $entityManager->persist($like);
                $entityManager->flush();
            }
            
// Rediriger vers la page précédente ou la page d'accueil
            return $this->redirectToRoute('app_home');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }
}