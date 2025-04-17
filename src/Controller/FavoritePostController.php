<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\FavoritePost;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\CommentaireType;
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
                    'isFavorite' => $isFavorited
                ]);
            }
            
            // Si ce n'est pas AJAX, rediriger vers la page précédente
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Une erreur est survenue lors de la modification des favoris.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            // Gestion d'erreur pour les requêtes non-AJAX
            $this->addFlash('error', 'Une erreur est survenue lors de la modification des favoris.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
        }
    }

    /**
     * Ajoute un commentaire à une photo
     */
    #[Route('/comment/add/{id}', name: 'app_add_comment', methods: ['POST'])]
    public function addComment(Photo $photo, Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Vous devez être connecté pour commenter.'], Response::HTTP_UNAUTHORIZED);
            }
            return $this->redirectToRoute('app_login');
        }

        $commentaire = new Commentaire();
        $commentaire->setUser($currentUser);
        $commentaire->setPhoto($photo);
        $commentaire->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($commentaire);
                $this->entityManager->flush();
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'id' => $commentaire->getId(),
                        'user' => $commentaire->getUser()->getPseudo(),
                        'message' => $commentaire->getMessage(),
                        'createdAt' => $commentaire->getCreatedAt()->format('H:i')
                    ]);
                }
                
                $this->addFlash('success', 'Votre commentaire a été ajouté.');
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Une erreur est survenue lors de l\'ajout du commentaire.'
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du commentaire.');
            }
        } elseif ($form->isSubmitted()) {
            if ($request->isXmlHttpRequest()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        
        // Rediriger vers la page précédente si ce n'est pas une requête AJAX
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('/saved-posts', name: 'app_saved_posts')]
    public function viewSavedPosts(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les posts favoris
        $favoritePosts = $this->favoritePostRepository->findBy(
            ['user' => $currentUser],
            ['createdAt' => 'DESC']
        );

        // Créer les formulaires de commentaires pour chaque photo
        $commentForms = [];
        foreach ($favoritePosts as $favoritePost) {
            $photo = $favoritePost->getPhoto();
            
            // Créer un nouveau commentaire lié à cette photo
            $commentaire = new Commentaire();
            $commentaire->setUser($currentUser);
            $commentaire->setPhoto($photo);
            
            // Créer le formulaire pour ce commentaire
            $commentForms[$photo->getId()] = $this->createForm(
                CommentaireType::class,
                $commentaire,
                [
                    'action' => $this->generateUrl('app_add_comment', ['id' => $photo->getId()]),
                    'method' => 'POST'
                ]
            )->createView();
            
            // Vérifier si l'utilisateur a liké cette photo
            $isLiked = false;
            foreach ($photo->getLikes() as $like) {
                if ($like->getUser() === $currentUser) {
                    $isLiked = true;
                    break;
                }
            }
            
            // Ajouter une propriété dynamique pour l'état du like
            $photo->isLikedByUser = $isLiked;
        }

        return $this->render('favorite_post/index.html.twig', [
            'favoritePosts' => $favoritePosts,
            'commentForms' => $commentForms,
            'currentUser' => $currentUser
        ]);
    }
}