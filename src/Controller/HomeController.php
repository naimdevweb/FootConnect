<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PhotoRepository $photoRepository): Response
    {
        // Utilisez directement le repository injecté
        $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Vérification pour le débogage
        // dd($photos); // Décommentez pour vérifier le contenu
        
        return $this->render('Home/index.html.twig', [
            'controller_name' => 'HomeController',
            'photos' => $photos,
        ]);
    }
}