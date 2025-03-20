<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Entity\Statut;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Repository\PhotoRepository;
use App\Repository\StatutRepository;
use App\Repository\UserRepository;
use App\Services\CommentFormService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Role\Role;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CommentFormService $commentFormService
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $currentUser = $this->getUser();
        $photos = $this->getFilteredPhotos($currentUser);
        $userStatuts = $this->getUserStatuts($photos, $currentUser);
        $commentForms = $this->createPhotoCommentForms($photos);

        if ($request->isMethod('POST')) {
            $statutRepository = $this->entityManager->getRepository(Statut::class);
            $response = $this->handleCommentSubmission($request, $statutRepository, $currentUser);

            if ($response instanceof Response) {
                return $response;
            }
        }

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'commentForms' => $commentForms,
            'userStatuts' => $userStatuts,
            'currentUser' => $currentUser
        ]);
    }

    private function getFilteredPhotos(?User $currentUser): array
    {
        return $currentUser instanceof User
            ? $this->photoRepository->findPhotosWithoutBlockedUsers($currentUser)
            : $this->photoRepository->findBy([], ['createdAt' => 'DESC']);
    }

    private function getUserStatuts(array $photos, ?User $currentUser): array
    {
        if (!$currentUser) {
            return [];
        }

        $userStatuts = [];
        $statutRepository = $this->entityManager->getRepository(Statut::class);

        foreach ($photos as $photo) {
            $photoUserId = $photo->getUser()->getId();
            $userStatuts[$photoUserId] = $statutRepository->findOneBy([
                'user' => $currentUser,
                'otherUser' => $photo->getUser()
            ]);
        }

        return $userStatuts;
    }

    private function createPhotoCommentForms(array $photos): array
    {
        $commentForms = [];
        $currentUser = $this->getUser();
        
        // Si l'utilisateur n'est pas connecté, retourner un tableau vide
        if (!$currentUser) {
            return $commentForms;
        }
    
        foreach ($photos as $photo) {
            $commentForm = $this->commentFormService->createCommentForm($photo);
            $commentForms[$photo->getId()] = $commentForm->createView();
        }
    
        return $commentForms;
    }

    private function handleCommentSubmission(Request $request, StatutRepository $statutRepository, ?User $currentUser): ?Response
    {
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }
    
        $comment = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);
    
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }
    
        try {
            $photoId = $form->get('photoId')->getData();
            $photo = $this->photoRepository->find($photoId);
    
            if (!$photo) {
                throw $this->createNotFoundException('Publication non trouvée');
            }
    
            if ($this->isUserBlocked($statutRepository, $photo->getUser(), $currentUser)) {
                $this->addFlash('error', 'Vous ne pouvez pas commenter les publications de cet utilisateur');
                return $this->redirectToRoute('app_home');
            }
    
            $this->saveComment($comment, $photo, $currentUser);
    
            $this->addFlash('success', 'Votre commentaire a été ajouté');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du commentaire');
        }
        
        return $this->redirectToRoute('app_home');
    }
    private function isUserBlocked(StatutRepository $statutRepository, User $photoUser, User $currentUser): bool
    {
        $statut = $statutRepository->findOneBy([
            'user' => $currentUser,
            'otherUser' => $photoUser
        ]);

        return $statut && $statut->isBlocked();
    }

    private function saveComment(Commentaire $comment, Photo $photo, User $user): void
    {
        $comment->setUser($user)
            ->setPhoto($photo)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository, LoggerInterface $logger): Response
    {
        try {
            // Récupération du terme de recherche
            $query = trim($request->query->get('query', ''));
            
            // Log pour débogage
            $logger->debug('Recherche effectuée avec le terme: "' . $query . '"');
            
            // Si la requête est vide ou trop courte, ne pas effectuer de recherche
            if (mb_strlen($query) < 2) {
                return $this->render('home/search_results.html.twig', [
                    'users' => [],
                    'query' => $query,
                    'debug' => 'Requête trop courte: ' . mb_strlen($query) . ' caractères'
                ]);
            }
            
            // Recherche des utilisateurs par pseudo
            $users = $userRepository->searchByPseudo($query);
            $logger->debug('Nombre d\'utilisateurs trouvés: ' . count($users));

            // Filtrer les utilisateurs pour ne pas afficher les administrateurs et les modérateurs
            $users = array_filter($users, function ($user) {
                return !in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_MODERATOR', $user->getRoles());
            });
            
            return $this->render('home/search_results.html.twig', [
                'users' => $users,
                'query' => $query
            ]);
        } catch (\Exception $e) {
            // Journal de l'erreur
            $logger->error('Erreur lors de la recherche : ' . $e->getMessage());
            
            // En cas d'erreur, on retourne un résultat vide
            return $this->render('home/search_results.html.twig', [
                'users' => [],
                'query' => $query ?? '',
                'error' => true,
                'errorMessage' => $e->getMessage()
            ]);
        }
    }
            
}
