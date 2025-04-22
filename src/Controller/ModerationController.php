<?php

namespace App\Controller;

use App\Controller\Trait\ModerationUtilityTrait;
use App\Controller\Trait\PhotoModerationTrait;
use App\Controller\Trait\UserModerationTrait;
use App\Controller\Trait\WarningModerationTrait;
use App\Entity\Photo;
use App\Entity\User;
use App\Entity\Warning;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use App\Repository\WarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Comment;
use App\Entity\Commentaire;
use App\Entity\Report;
use App\Repository\CommentaireRepository;
use App\Repository\ReportRepository;

/**
 * Contrôleur pour les fonctionnalités de modération
 */
#[IsGranted('ROLE_MODERATEUR')]
class ModerationController extends AbstractController
{
    use ModerationUtilityTrait;
    use PhotoModerationTrait;
    use UserModerationTrait;
    use WarningModerationTrait;

    /**
     * Affiche le tableau de bord de modération principal
     */
   /**
 * Affiche le tableau de bord de modération principal
 */
#[Route('/moderation', name: 'app_moderation_dashboard', methods: ['GET'])]
public function dashboard(
    PhotoRepository $photoRepository,
    UserRepository $userRepository,
    WarningRepository $warningRepository,
    ReportRepository $reportRepository
): Response {
    // Initialiser les variables par défaut pour éviter les erreurs
    $totalUsers = 0;
    $totalPhotos = 0;
    $totalWarnings = 0;
    $pendingReports = 0;
    $latestPhotos = [];

    try {
        // Récupérer les statistiques
        $totalUsers = $userRepository->count([]);
        $totalPhotos = $photoRepository->count([]);
        $warnings = $warningRepository->findAll();
        $totalWarnings = count($warnings);
        
        // Récupérer le nombre de signalements en attente
        $pendingReports = $reportRepository->countByStatus(Report::STATUS_PENDING);

        // Photos récentes
        $latestPhotos = $photoRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue lors du chargement du tableau de bord: ' . $e->getMessage());
    }

    // Toujours rendre le template, même en cas d'erreur
    return $this->render('warning/dashboard.html.twig', [
        'latestPhotos' => $latestPhotos,
        'totalUsers' => $totalUsers,
        'totalPhotos' => $totalPhotos,
        'totalWarnings' => $totalWarnings,
        'pendingReports' => $pendingReports
    ]);
}

    /**
     * Liste toutes les photos pour modération
     */
    #[Route('/moderation/photos', name: 'app_moderator_photos', methods: ['GET'])]
    public function moderatorPhotos(PhotoRepository $photoRepository): Response
    {
        return $this->listPhotos($photoRepository);
    }

    /**
     * Supprime une photo
     */
    #[Route('/moderation/photos/{id}/delete', name: 'app_moderator_delete_photo', methods: ['POST'])]
    public function moderatorDeletePhoto(
        Request $request,
        Photo $photo,
        EntityManagerInterface $entityManager
    ): Response {
        return $this->deletePhoto($request, $photo, $entityManager);
    }


/**
 * Supprime un commentaire
 */
#[Route('/moderation/comment/{id}/delete', name: 'app_moderator_delete_comment', methods: ['POST'])]
public function moderatorDeleteComment(
    Request $request,
    Commentaire $comment,
    EntityManagerInterface $entityManager
): Response {
    // Vérification du token CSRF
    if ($this->isCsrfTokenValid('delete_comment' . $comment->getId(), $request->request->get('_token'))) {
        try {
            // Supprimer le commentaire
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du commentaire: ' . $e->getMessage());
        }
    } else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }
    
    // Rediriger vers la page des publications
    return $this->redirectToRoute('app_moderation_posts');
}

    /**
     * Envoi d'un avertissement à un utilisateur depuis la modération
     */
    #[Route('/moderation/warn-user/{id}', name: 'app_moderator_warn_user', methods: ['GET', 'POST'])]
    public function moderatorWarnUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        return $this->warnUser($user, $request, $entityManager);
    }

    /**
     * Affiche la liste des avertissements
     */
    #[Route('/warning', name: 'app_warning_index', methods: ['GET'])]
    public function warningIndex(WarningRepository $warningRepository): Response
    {
        return $this->warningList($warningRepository);
    }

    /**
     * Affiche le tableau de bord des avertissements
     */
    #[Route('/warning/dashboard', name: 'app_warning_dashboard', methods: ['GET'])]
    public function warningDashboardRoute(WarningRepository $warningRepository): Response
    {
        return $this->warningDashboard($warningRepository);
    }

    /**
     * Crée un nouvel avertissement
     */
    #[Route('/warning/new', name: 'app_warning_new', methods: ['GET', 'POST'])]
    public function warningNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->newWarning($request, $entityManager);
    }

    /**
     * Affiche les détails d'un avertissement
     */
    #[Route('/warning/{id}', name: 'app_warning_show', methods: ['GET'])]
    public function warningShow(Warning $warning, EntityManagerInterface $entityManager): Response
    {
        return $this->showWarning($warning, $entityManager);
    }

    /**
     * Modifier un avertissement existant
     */
    #[Route('/warning/{id}/edit', name: 'app_warning_edit', methods: ['GET', 'POST'])]
    public function warningEdit(Request $request, Warning $warning, EntityManagerInterface $entityManager): Response
    {
        return $this->editWarning($request, $warning, $entityManager);
    }

    /**
     * Supprimer un avertissement
     */
    #[Route('/warning/{id}/delete', name: 'app_warning_delete', methods: ['POST'])]
    public function warningDelete(Request $request, Warning $warning, EntityManagerInterface $entityManager): Response
    {
        return $this->deleteWarning($request, $warning, $entityManager);
    }

    /**
     * Permet de bannir ou débannir un utilisateur
     */
    #[Route('/moderation/utilisateur/{id}/bannir', name: 'app_moderation_ban_user', methods: ['GET', 'POST'])]
    public function moderationBanUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        return $this->banUser($user, $request, $entityManager);
    }

    /**
     * Liste tous les utilisateurs avec options de modération
     */
    #[Route('/moderation/utilisateurs', name: 'app_moderation_users', methods: ['GET'])]
    public function moderationUsers(UserRepository $userRepository): Response
    {
        return $this->listUsers($userRepository);
    }

    /**
     * Liste de toutes les publications (photos) avec options de modération
     */
    #[Route('/moderation/publications', name: 'app_moderation_posts', methods: ['GET'])]
    public function moderationPosts(PhotoRepository $photoRepository): Response
    {
        $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Rendu direct du template au lieu d'utiliser la méthode du trait
        return $this->render('warning/posts.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/moderation/reports', name: 'app_moderation_reports')]
    public function reports(ReportRepository $reportRepository): Response
    {
        try {
            // Récupérer les signalements par statut
            $pendingReports = $reportRepository->findBy(['status' => Report::STATUS_PENDING], ['createdAt' => 'DESC']);
            $reviewingReports = $reportRepository->findBy(['status' => Report::STATUS_REVIEWING], ['createdAt' => 'DESC']);
            $resolvedReports = $reportRepository->findBy(
                ['status' => [Report::STATUS_RESOLVED, Report::STATUS_DISMISSED]], 
                ['createdAt' => 'DESC'],
                10 // Limiter à 10 signalements résolus récents
            );
            
            // Nombre de signalements par statut pour le tableau de bord
            $reportStats = [
                'pending' => $reportRepository->countByStatus(Report::STATUS_PENDING),
                'reviewing' => $reportRepository->countByStatus(Report::STATUS_REVIEWING),
                'resolved' => $reportRepository->countByStatus(Report::STATUS_RESOLVED),
                'dismissed' => $reportRepository->countByStatus(Report::STATUS_DISMISSED),
            ];
            
            return $this->render('warning/reports.html.twig', [
                'pendingReports' => $pendingReports,
                'reviewingReports' => $reviewingReports,
                'resolvedReports' => $resolvedReports,
                'reportStats' => $reportStats,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des signalements: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
 * Affiche les détails d'un signalement
 */
#[Route('/moderation/report/{id}', name: 'app_moderation_report_details', methods: ['GET'])]
public function reportDetails(
    Report $report,
    UserRepository $userRepository,
    CommentaireRepository $commentaireRepository,
    PhotoRepository $photoRepository,
    EntityManagerInterface $entityManager
): Response {
    try {
        // Récupérer les détails de l'élément signalé
        $reportedItem = null;
        
        switch ($report->getReportType()) {
            case Report::TYPE_USER:
                $reportedItem = $userRepository->find($report->getReportedItemId());
                break;
                
            case Report::TYPE_COMMENT:
                $reportedItem = $commentaireRepository->find($report->getReportedItemId());
                break;
                
            case Report::TYPE_POST:
                $reportedItem = $photoRepository->find($report->getReportedItemId());
                break;
        }
        
        // Si l'élément signalé n'existe plus, mettre à jour le statut du signalement
        if (!$reportedItem && $report->getStatus() === Report::STATUS_PENDING) {
            $report->setStatus(Report::STATUS_RESOLVED);
            $report->setModeratorNote('L\'élément signalé n\'existe plus.');
            $report->setModerator($this->getUser());
            $report->setResolvedAt(new \DateTime());
            $entityManager->flush();
        }
        
        return $this->render('warning/report_details.html.twig', [
            'report' => $report,
            'reportedItem' => $reportedItem,
        ]);
    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue lors du chargement des détails du signalement: ' . $e->getMessage());
        return $this->redirectToRoute('app_moderation_reports');
    }
}

/**
 * Met à jour le statut d'un signalement
 */
#[Route('/moderation/report/{id}/update-status', name: 'app_moderation_update_status', methods: ['POST'])]
public function updateStatus(
    Request $request,
    Report $report,
    EntityManagerInterface $entityManager,
    UserRepository $userRepository,
    CommentaireRepository $commentaireRepository,
    PhotoRepository $photoRepository
): Response {
    // Vérification CSRF
    if (!$this->isCsrfTokenValid('update-report-status' . $report->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_moderation_report_details', ['id' => $report->getId()]);
    }
    
    $action = $request->request->get('action');
    $moderatorNote = $request->request->get('moderator_note');
    
    try {
        // Mise à jour du statut du signalement
        $report->setModerator($this->getUser());
        $report->setModeratorNote($moderatorNote);
        
        switch ($action) {
            case 'reviewing':
                $report->setStatus(Report::STATUS_REVIEWING);
                $message = 'Le signalement est maintenant en cours d\'examen.';
                break;
                
            case 'dismiss':
                $report->setStatus(Report::STATUS_DISMISSED);
                $report->setResolvedAt(new \DateTime());
                $message = 'Le signalement a été rejeté.';
                break;
                
            case 'resolve':
                $report->setStatus(Report::STATUS_RESOLVED);
                $report->setResolvedAt(new \DateTime());
                $message = 'Le signalement a été résolu.';
                break;
                
            case 'delete_content':
                $report->setStatus(Report::STATUS_RESOLVED);
                $report->setResolvedAt(new \DateTime());
                
                // Supprimer le contenu signalé
                $reportedItem = null;
                
                switch ($report->getReportType()) {
                    case Report::TYPE_COMMENT:
                        $reportedItem = $commentaireRepository->find($report->getReportedItemId());
                        if ($reportedItem) {
                            $entityManager->remove($reportedItem);
                        }
                        break;
                        
                    case Report::TYPE_POST:
                        $reportedItem = $photoRepository->find($report->getReportedItemId());
                        if ($reportedItem) {
                            $entityManager->remove($reportedItem);
                        }
                        break;
                }
                
                $message = 'Le contenu signalé a été supprimé et le signalement résolu.';
                break;
                
            case 'warn_user':
                $report->setStatus(Report::STATUS_RESOLVED);
                $report->setResolvedAt(new \DateTime());
                
                // Ajouter un avertissement à l'utilisateur concerné
                $userId = null;
                
                switch ($report->getReportType()) {
                    case Report::TYPE_USER:
                        $userId = $report->getReportedItemId();
                        break;
                        
                    case Report::TYPE_COMMENT:
                        $comment = $commentaireRepository->find($report->getReportedItemId());
                        if ($comment) {
                            $userId = $comment->getUser()->getId();
                        }
                        break;
                        
                    case Report::TYPE_POST:
                        $post = $photoRepository->find($report->getReportedItemId());
                        if ($post) {
                            $userId = $post->getUser()->getId();
                        }
                        break;
                }
                
                if ($userId) {
                    $user = $userRepository->find($userId);
                    if ($user) {
                        // Créer un avertissement
                        $warning = new Warning();
                        $warning->setUser($user);
                        $warning->setReason('Suite à un signalement');
                        $warning->setDescription($moderatorNote ?: 'Contenu signalé par un utilisateur');
                        $warning->setModerator($this->getUser());
                        $entityManager->persist($warning);
                        
                        $message = 'Un avertissement a été envoyé à l\'utilisateur et le signalement a été résolu.';
                    }
                }
                break;
                
            default:
                throw new \InvalidArgumentException('Action non reconnue');
        }
        
        $entityManager->flush();
        $this->addFlash('success', $message);
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('app_moderation_report_details', ['id' => $report->getId()]);
}
}