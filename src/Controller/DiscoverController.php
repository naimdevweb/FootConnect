<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscoverController extends AbstractController
{
    /**
     * Page d'exploration du contenu
     * 
     * Affiche les photos populaires, utilisateurs suggérés et tags populaires
     */
    #[Route('/discover', name: 'app_discover')]
    public function index(PhotoRepository $photoRepository, UserRepository $userRepository): Response
    {
        try {
            // Récupérer les photos populaires (récentes)
            $popularPhotos = $photoRepository->findBy(
                [], // Critères
                ['createdAt' => 'DESC'], // Tri par date de création
                12 // Limite
            );
            
            // Récupérer des utilisateurs suggérés (triés par ID)
            // NOTE: L'entité User n'a pas de champ createdAt
            $suggestedUsers = $userRepository->findBy(
                [], // Critères
                ['id' => 'DESC'], // Tri par ID au lieu de createdAt
                5 // Limite
            );
            
            // Tags populaires (à adapter selon votre modèle de données)
            $popularTags = ['Football', 'Training', 'TeamSpirit', 'Match', 'Skills'];
            
            return $this->render('discover/index.html.twig', [
                'popularPhotos' => $popularPhotos,
                'suggestedUsers' => $suggestedUsers,
                'popularTags' => $popularTags
            ]);
        } catch (\Exception $e) {
            // Gestion d'erreur selon les bonnes pratiques
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la page de découverte.');
            
            // En mode développement, on peut logger l'erreur ou l'afficher
            if ($this->getParameter('kernel.environment') === 'dev') {
                throw $e;
            }
            
            // Redirection vers la page d'accueil en cas d'erreur
            return $this->redirectToRoute('app_home');
        }
    }
}