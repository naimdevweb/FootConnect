<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/HomeController.php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Entity\Statut;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Repository\PhotoRepository;
use App\Repository\StatutRepository;
use App\Services\CommentFormService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la page d'accueil qui affiche le fil d'actualités
 */
class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil avec le fil d'actualités filtré
     *
     * @param Request $request Requête HTTP
     * @param PhotoRepository $photoRepository Repository pour les photos
     * @param CommentFormService $commentFormService Service de gestion des formulaires de commentaires
     * @param EntityManagerInterface $entityManager Manager d'entités Doctrine
     * @return Response
     */
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        PhotoRepository $photoRepository,
        CommentFormService $commentFormService,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Récupérer l'utilisateur courant
            $currentUser = $this->getUser();
            $photos = $this->getFilteredPhotos($photoRepository, $currentUser);
            
            // Récupérer les statuts entre l'utilisateur courant et les auteurs des photos
            $userStatuts = $this->getUserStatuts($entityManager, $photos, $currentUser);
            
            // Créer les formulaires de commentaires pour chaque photo
            $commentForms = $this->createPhotoCommentForms($photos, $commentFormService);
            
            // Traiter la soumission d'un formulaire de commentaire
            if ($request->isMethod('POST')) {
                $statutRepository = $entityManager->getRepository(Statut::class);
                $result = $this->handleCommentSubmission(
                    $request, 
                    $photoRepository, 
                    $entityManager, 
                    $statutRepository, 
                    $currentUser
                );
                
                if ($result instanceof Response) {
                    return $result;
                }
            }
            
            // Afficher la vue avec toutes les données nécessaires
            return $this->render('home/index.html.twig', [
                'photos' => $photos,
                'commentForms' => $commentForms,
                'userStatuts' => $userStatuts,
                'currentUser' => $currentUser
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la page : ' . $e->getMessage());
            return $this->render('home/index.html.twig', [
                'photos' => [],
                'commentForms' => [],
                'userStatuts' => [],
                'currentUser' => $currentUser
            ]);
        }
    }
    
    /**
     * Récupère les photos filtrées selon l'utilisateur connecté
     *
     * @param PhotoRepository $photoRepository Repository pour les photos
     * @param User|null $currentUser Utilisateur courant
     * @return array Liste des photos filtrées
     */
    private function getFilteredPhotos(PhotoRepository $photoRepository, ?User $currentUser): array
    {
        if ($currentUser instanceof User) {
            // Si un utilisateur est connecté, filtrer les photos des utilisateurs bloqués
            return $photoRepository->findPhotosWithoutBlockedUsers($currentUser);
        }
        
        // Sinon, afficher toutes les photos
        return $photoRepository->findBy([], ['createdAt' => 'DESC']);
    }
    
    /**
     * Récupère les statuts entre l'utilisateur courant et les auteurs des photos
     *
     * @param EntityManagerInterface $entityManager Manager d'entités Doctrine
     * @param array $photos Liste des photos
     * @param User|null $currentUser Utilisateur courant
     * @return array Tableau des statuts indexés par ID d'utilisateur
     */
    private function getUserStatuts(EntityManagerInterface $entityManager, array $photos, ?User $currentUser): array
    {
        $userStatuts = [];
        
        if ($currentUser) {
            $statutRepository = $entityManager->getRepository(Statut::class);
            foreach ($photos as $photo) {
                $statut = $statutRepository->findOneBy([
                    'user' => $currentUser,
                    'otherUser' => $photo->getUser()
                ]);
                $userStatuts[$photo->getUser()->getId()] = $statut;
            }
        }
        
        return $userStatuts;
    }
    
    /**
     * Crée les formulaires de commentaires pour chaque photo
     *
     * @param array $photos Liste des photos
     * @param CommentFormService $commentFormService Service de gestion des formulaires
     * @return array Tableau des formulaires indexés par ID de photo
     */
    private function createPhotoCommentForms(array $photos, CommentFormService $commentFormService): array
    {
        $commentForms = [];
        
        foreach ($photos as $photo) {
            $commentForm = $commentFormService->createCommentForm($photo);
            $commentForms[$photo->getId()] = $commentForm->createView();
        }
        
        return $commentForms;
    }
    
    /**
     * Gère la soumission d'un formulaire de commentaire
     *
     * @param Request $request Requête HTTP
     * @param PhotoRepository $photoRepository Repository pour les photos
     * @param EntityManagerInterface $entityManager Manager d'entités Doctrine
     * @param StatutRepository $statutRepository Repository pour les statuts
     * @param User|null $currentUser Utilisateur courant
     * @return Response|null Redirection ou null si aucune action n'est nécessaire
     */
    private function handleCommentSubmission(
        Request $request,
        PhotoRepository $photoRepository,
        EntityManagerInterface $entityManager,
        StatutRepository $statutRepository,
        ?User $currentUser
    ): ?Response {
        $comment = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $photoId = $form->get('photoId')->getData();
            $photo = $photoRepository->find($photoId);
            
            if (!$photo) {
                throw $this->createNotFoundException('Publication non trouvée');
            }
            
            if (!$currentUser) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour commenter');
            }
            
            // Vérifier si l'utilisateur est bloqué
            $statutBlocked = $statutRepository->findOneBy([
                'user' => $photo->getUser(),
                'otherUser' => $currentUser,
                'isBlocked' => true
            ]);
            
            if ($statutBlocked) {
                $this->addFlash('error', 'Vous ne pouvez pas commenter les publications de cet utilisateur');
                return $this->redirectToRoute('app_home');
            }
            
            $comment->setUser($currentUser)
                ->setPhoto($photo)
                ->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a été ajouté');
            return $this->redirectToRoute('app_home');
        }
        
        return null;
    }
}