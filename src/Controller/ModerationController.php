<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/ModerationController.php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Entity\Warning;
use App\Form\UserBanType;
use App\Form\WarningType;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use App\Repository\WarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour les fonctionnalités de modération
 */
#[IsGranted('ROLE_MODERATEUR')]
class ModerationController extends AbstractController
{
    /**
     * Affiche le tableau de bord de modération
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
            $this->addFlash('error', 'Une erreur est survenue lors du chargement du tableau de bord: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }

    /**
     * Liste toutes les photos pour modération
     */
    #[Route('/moderation/photos', name: 'app_moderator_photos', methods: ['GET'])]
    public function listPhotos(PhotoRepository $photoRepository): Response
    {
        try {
            $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);

            // Récupérer les commentaires associés à chaque photo
            $photosWithComments = [];
            foreach ($photos as $photo) {
                $comments = $photo->getComments(); // Utilisation de getCommentaires() selon la convention de nommage
                $photosWithComments[] = [
                    'photo' => $photo,
                    'comments' => $comments
                ];
            }

            return $this->render('moderation/photos.html.twig', [
                'photosWithComments' => $photosWithComments
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des photos: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
     * Supprime une photo
     */
    #[Route('/moderation/photos/{id}/delete', name: 'app_moderator_delete_photo', methods: ['POST'])]
    public function deletePhoto(
        Request $request,
        Photo $photo,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            if (!$this->isCsrfTokenValid('delete' . $photo->getId(), $request->request->get('_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            // Stocker les informations de la photo pour le message flash
            $photoId = $photo->getId();

            // Supprimer le fichier physique si possible
            $photoPath = $this->getParameter('photos_directory') . '/' . $photo->getPhotoUrl();
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }

            $entityManager->remove($photo);
            $entityManager->flush();

            $this->addFlash('success', 'Photo #' . $photoId . ' supprimée avec succès.');

            return $this->redirectToRoute('app_moderator_photos');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderator_photos');
        }
    }

    /**
     * Envoi d'un avertissement à un utilisateur depuis la modération
     */
    #[Route('/moderation/warn-user/{id}', name: 'app_moderator_warn_user', methods: ['GET', 'POST'])]
    public function warnUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $warning = new Warning();
            $warning->setUser($user);
            $warning->setModerator($this->getUser());
            $warning->setCreatedAtValue(new \DateTime());
            $warning->setViewed(false);

            $form = $this->createForm(WarningType::class, $warning);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($warning);
                $entityManager->flush();

                $this->addFlash('success', 'Avertissement envoyé à ' . $user->getPseudo());
                return $this->redirectToRoute('app_moderator_photos');
            }

            return $this->render('warning/new.html.twig', [
                'form' => $form,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'avertissement: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderator_photos');
        }
    }

    /**
     * Affiche la liste des avertissements
     */

    #[Route('/warning', name: 'app_warning_index', methods: ['GET'])]
    public function warningList(WarningRepository $warningRepository): Response
    {
        try {
            return $this->render('warning/warning_index.html.twig', [
                'warnings' => $warningRepository->findAll(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des avertissements: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
     * Affiche le tableau de bord des avertissements
     */
    #[Route('/warning/dashboard', name: 'app_warning_dashboard', methods: ['GET'])]
    public function warningDashboard(WarningRepository $warningRepository): Response
    {
        try {
            $warnings = $warningRepository->findAll();

            // Filtrer les avertissements non lus
            $unviewedWarnings = array_filter($warnings, function ($warning) {
                return !$warning->isViewed();
            });

            // Récupérer les avertissements récents triés par date
            usort($warnings, function ($a, $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });
            $recentWarnings = array_slice($warnings, 0, 10);

            return $this->render('warning/dashboard.html.twig', [
                'warnings' => $warnings,
                'unviewedWarnings' => $unviewedWarnings,
                'recentWarnings' => $recentWarnings,
                'totalWarnings' => count($warnings),
                'unviewedCount' => count($unviewedWarnings)
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement du tableau de bord: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
     * Crée un nouvel avertissement
     */
    #[Route('/warning/new', name: 'app_warning_new', methods: ['GET', 'POST'])]
    public function newWarning(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $warning = new Warning();
            $warning->setModerator($this->getUser());
            $warning->setCreatedAtValue(new \DateTime());
            $warning->setViewed(false);

            $form = $this->createForm(WarningType::class, $warning);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($warning);
                $entityManager->flush();

                $this->addFlash('success', 'Avertissement créé avec succès.');
                return $this->redirectToRoute('app_warning_index');
            }

            return $this->render('warning/new.html.twig', [
                'warning' => $warning,
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'avertissement: ' . $e->getMessage());
            return $this->redirectToRoute('app_warning_index');
        }
    }

    /**
     * Affiche les détails d'un avertissement
     */
    #[Route('/warning/{id}', name: 'app_warning_show', methods: ['GET'])]
    public function showWarning(Warning $warning, EntityManagerInterface $entityManager): Response
    {
        try {
            // Marquer l'avertissement comme lu s'il ne l'est pas déjà
            if (!$warning->isViewed()) {
                $warning->setViewed(true);
                $entityManager->flush();
            }

            return $this->render('warning/show.html.twig', [
                'warning' => $warning,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'affichage de l\'avertissement: ' . $e->getMessage());
            return $this->redirectToRoute('app_warning_index');
        }
    }

    /**
     * Modifier un avertissement existant
     */
    #[Route('/warning/{id}/edit', name: 'app_warning_edit', methods: ['GET', 'POST'])]
    public function editWarning(Request $request, Warning $warning, EntityManagerInterface $entityManager): Response
    {
        try {
            $form = $this->createForm(WarningType::class, $warning);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Avertissement modifié avec succès.');
                return $this->redirectToRoute('app_warning_index');
            }

            return $this->render('warning/edit.html.twig', [
                'warning' => $warning,
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'avertissement: ' . $e->getMessage());
            return $this->redirectToRoute('app_warning_index');
        }
    }

    /**
     * Supprimer un avertissement
     */
    #[Route('/warning/{id}/delete', name: 'app_warning_delete', methods: ['POST'])]
    public function deleteWarning(Request $request, Warning $warning, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete' . $warning->getId(), $request->request->get('_token'))) {
                $entityManager->remove($warning);
                $entityManager->flush();
                $this->addFlash('success', 'Avertissement supprimé avec succès.');
            }

            return $this->redirectToRoute('app_warning_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'avertissement: ' . $e->getMessage());
            return $this->redirectToRoute('app_warning_index');
        }
    }

    /**
     * Permet de bannir ou débannir un utilisateur
     */
    #[Route('/moderation/utilisateur/{id}/bannir', name: 'app_moderation_ban_user', methods: ['GET', 'POST'])]
    public function banUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Création du formulaire pour la raison du bannissement
            $form = $this->createForm(UserBanType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Inverser le statut de bannissement
                $currentStatus = $user->isBanned();
                $user->setBanned(!$currentStatus);

                // Si on banne l'utilisateur, enregistrer la raison
                if (!$currentStatus) {
                    $user->setBanReason($form->get('banReason')->getData());
                    $user->setBannedAt(new \DateTime());
                    $this->addFlash('success', 'L\'utilisateur ' . $user->getPseudo() . ' a été banni.');
                } else {
                    $user->setBanReason(null);
                    $user->setBannedAt(null);
                    $this->addFlash('success', 'L\'utilisateur ' . $user->getPseudo() . ' a été débanni.');
                }

                $entityManager->flush();
                return $this->redirectToRoute('app_moderation_users');
            }

            return $this->render('moderation/ban_user.html.twig', [
                'user' => $user,
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
     * Liste tous les utilisateurs avec options de modération
     */
    #[Route('/moderation/utilisateurs', name: 'app_moderation_users', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository): Response
    {
        try {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

            return $this->render('moderation/users.html.twig', [
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des utilisateurs: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    /**
     * Liste de toutes les publications (photos) avec options de modération
     */
    #[Route('/moderation/publications', name: 'app_moderation_posts', methods: ['GET'])]
    public function listPosts(PhotoRepository $photoRepository): Response
    {
        try {
            $photos = $photoRepository->findBy([], ['createdAt' => 'DESC']);

            return $this->render('moderation/posts.html.twig', [
                'photos' => $photos,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des publications: ' . $e->getMessage());
            return $this->redirectToRoute('app_moderation_dashboard');
        }
    }

    // Le reste du code existant...


}
