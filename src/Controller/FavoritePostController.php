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


#[Route('/toggle-favorite/{id}', name: 'app_toggle_favorite', methods: ['POST','GET'])]
public function toggleFavorite(Photo $photo, Request $request): Response
{
    try {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Vous devez être connecté pour enregistrer.'], Response::HTTP_UNAUTHORIZED);
            }
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si le post est déjà dans les favoris
        $existingFavorite = $this->favoritePostRepository->findOneBy([
            'user' => $currentUser,
            'photo' => $photo
        ]);

        $isFavorited = false;

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
            $isFavorited = true;
        }

        $this->entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'isFavorited' => $isFavorited
            ]);
        }
        
        // Si ce n'est pas AJAX, rediriger vers la page précédente
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_homepage'));
    } catch (\Exception $e) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la modification des favoris.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // Gestion d'erreur pour les requêtes non-AJAX
        $this->addFlash('error', 'Une erreur est survenue lors de la modification des favoris.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_homepage'));
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