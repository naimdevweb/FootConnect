<?php

namespace App\Controller;

use App\Entity\FavoritePost;
use App\Entity\Photo;
use App\Repository\FavoritePostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class FavoritePostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoritePostRepository $favoritePostRepository
    ) {}

    #[Route('/toggle-favorite/{id}', name: 'app_toggle_favorite')]
public function toggleFavorite(Photo $photo, Request $request): Response
{
    try {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si le post est déjà dans les favoris
        $existingFavorite = $this->favoritePostRepository->findOneBy([
            'user' => $currentUser,
            'photo' => $photo
        ]);

        if ($existingFavorite) {
            // Retirer des favoris
            $this->entityManager->remove($existingFavorite);
        } else {
            // Ajouter aux favoris
            $favoritePost = new FavoritePost();
            $favoritePost->setUser($currentUser);
            $favoritePost->setPhoto($photo);
            $favoritePost->setCreatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($favoritePost);
        }

        $this->entityManager->flush();
        
        // Rediriger vers la page d'où provient la requête
        $referer = $request->headers->get('referer');
        
        return $this->redirect($referer ?: $this->generateUrl('app_saved_posts'));
        
    } catch (\Exception $e) {
        // En cas d'erreur, rediriger vers la page des favoris avec un message flash
        $this->addFlash('error', 'Une erreur est survenue lors de la modification des favoris.');
        return $this->redirectToRoute('app_saved_posts');
    }
}

    #[Route('/saved-posts', name: 'app_saved_posts')]
    public function viewSavedPosts(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $favoritePosts = $this->favoritePostRepository->findBy(
            ['user' => $currentUser],
            ['createdAt' => 'DESC']
        );

        return $this->render('favorite_post/index.html.twig', [
            'favoritePosts' => $favoritePosts,
            'currentUser' => $currentUser
        ]);
    }
}