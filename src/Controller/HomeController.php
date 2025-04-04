<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use App\Services\CommentFormService;
use App\Services\CommentaireService;
use App\Services\PhotoService;
use App\Services\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CommentFormService $commentFormService,
        private readonly CommentaireService $commentaireService,
        private readonly PhotoService $photoService,
        private readonly SearchService $searchService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $currentUser = $this->getUser();
        $filterType = $request->query->get('type');
        
        // Récupérer les photos filtrées selon le type (following/all)
        $photos = $this->photoService->getFilteredPhotos($currentUser, $filterType);
        $userStatuts = $this->photoService->getUserStatuts($photos, $currentUser);
        $commentForms = $this->commentFormService->createPhotoCommentForms($photos, $currentUser);
        
        // Déterminer le titre de la page selon le filtre
        $pageTitle = $filterType === 'following' ? 'Publications de vos abonnements' : 'Fil d\'actualités';

        if ($request->isMethod('POST')) {
            $response = $this->commentaireService->handleCommentSubmission($request, $currentUser);
            if ($response instanceof Response) {
                return $response;
            }
        }

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'commentForms' => $commentForms,
            'userStatuts' => $userStatuts,
            'currentUser' => $currentUser,
            'filterType' => $filterType,
            'pageTitle' => $pageTitle
        ]);
    }
    

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, LoggerInterface $logger): Response
    {
        try {
            $query = trim($request->query->get('query', ''));
            $logger->debug('Recherche effectuée avec le terme: "' . $query . '"');

            if (mb_strlen($query) < 2) {
                return $this->render('home/search_results.html.twig', [
                    'users' => [],
                    'query' => $query,
                    'debug' => 'Requête trop courte: ' . mb_strlen($query) . ' caractères'
                ]);
            }

            $users = $this->searchService->searchUsers($query);
            $logger->debug('Nombre d\'utilisateurs trouvés: ' . count($users));

            return $this->render('home/search_results.html.twig', [
                'users' => $users,
                'query' => $query
            ]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la recherche : ' . $e->getMessage());
            return $this->render('home/search_results.html.twig', [
                'users' => [],
                'query' => $query ?? '',
                'error' => true,
                'errorMessage' => $e->getMessage()
            ]);
        }
    }
}