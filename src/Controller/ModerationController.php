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
    #[Route('/moderation', name: 'app_moderation_dashboard', methods: ['GET'])]
    public function dashboard(
        PhotoRepository $photoRepository,
        UserRepository $userRepository,
        WarningRepository $warningRepository
    ): Response {
        try {
            // Récupérer les statistiques
            $totalUsers = $userRepository->count([]);
            $totalPhotos = $photoRepository->count([]);
            $warnings = $warningRepository->findAll();
            $totalWarnings = count($warnings);

            // Photos récentes
            $latestPhotos = $photoRepository->findBy(
                [],
                ['createdAt' => 'DESC'],
                10
            );

            return $this->render('warning/dashboard.html.twig', [
                'latestPhotos' => $latestPhotos,
                'totalUsers' => $totalUsers,
                'totalPhotos' => $totalPhotos,
                'totalWarnings' => $totalWarnings
            ]);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'chargement du tableau de bord', 'app_home');
        }
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
}