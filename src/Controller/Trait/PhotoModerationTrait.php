<?php

namespace App\Controller\Trait;

use App\Entity\Photo;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait de gestion des photos pour la modération
 */
trait PhotoModerationTrait
{
    /**
     * Liste toutes les photos pour modération
     */
    public function listPhotos(PhotoRepository $photoRepository): Response
    {
        try {
            $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);
            $photosWithComments = $this->preparePhotosWithComments($photos);

            // Utilisation de posts.html.twig au lieu de photos.html.twig
            return $this->render('warning/posts.html.twig', [
                'photos' => $photos
            ]);
        } catch (\Exception $e) {
            // Utilisation de la méthode générique handleException
            return $this->handleException($e, 'chargement des photos', 'app_moderation_dashboard');
        }
    }

    /**
     * Supprime une photo
     */
    public function deletePhoto(
        Request $request,
        Photo $photo,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            if (!$this->isCsrfTokenValid('delete' . $photo->getId(), $request->request->get('_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $photoId = $photo->getId();
            $this->deletePhotoFile($photo);
            
            $entityManager->remove($photo);
            $entityManager->flush();

            $this->addFlash('success', 'Photo #' . $photoId . ' supprimée avec succès.');

            // Redirection vers la liste des publications après suppression
            return $this->redirectToRoute('app_moderation_posts');
        } catch (\Exception $e) {
            // Utilisation de la méthode générique handleException
            return $this->handleException($e, 'suppression', 'app_moderation_posts');
        }
    }

    /**
     * Liste de toutes les publications avec options de modération
     */
    public function listPosts(PhotoRepository $photoRepository): Response
    {
        try {
            $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);

            return $this->render('warning/posts.html.twig', [
                'photos' => $photos,
            ]);
        } catch (\Exception $e) {
            // Utilisation de la méthode générique handleException
            return $this->handleException($e, 'chargement des publications', 'app_moderation_dashboard');
        }
    }

    /**
     * Prépare les données pour les photos avec leurs commentaires
     */
    private function preparePhotosWithComments(array $photos): array
    {
        $photosWithComments = [];
        foreach ($photos as $photo) {
            $comments = $photo->getComments();
            $photosWithComments[] = [
                'photo' => $photo,
                'comments' => $comments
            ];
        }
        
        return $photosWithComments;
    }

    /**
     * Supprime le fichier physique d'une photo
     */
    private function deletePhotoFile(Photo $photo): void
    {
        $photoPath = $this->getParameter('photos_directory') . '/' . $photo->getPhotoUrl();
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }

    /**
     * Gère les exceptions spécifiques aux opérations sur les photos
     * 
     * Note: Renommée pour éviter le conflit avec ModerationUtilityTrait
     */
    private function handleException(\Exception $e, string $action, string $redirectRoute): Response
    {
        $this->addFlash('error', 'Une erreur est survenue lors de l\'' . $action . ': ' . $e->getMessage());
        return $this->redirectToRoute($redirectRoute);
    }
}