<?php

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
        
        $user = $this->getUser();

        $photo = $entityManager->getRepository(Photo::class)->find($id);
        $like = new Like();
        $like->setUser($user);
        $like->setPhoto($photo);
        $entityManager->persist($like);
        $entityManager->flush();
        
        return $this->redirectToRoute('app_home');
    }
}
