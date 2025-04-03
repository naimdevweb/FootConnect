<?php

namespace App\Controller\Trait;

use App\Entity\User;
use App\Entity\Warning;
use App\Form\WarningType;
use App\Repository\WarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait de gestion des avertissements pour la modération
 */
trait WarningModerationTrait
{
    /**
     * Envoi d'un avertissement à un utilisateur
     */
    public function warnUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $warning = $this->createWarning($user);
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
            return $this->handleModerationException($e, 'envoi de l\'avertissement', 'app_moderator_photos');
        }
    }

    /**
     * Affiche la liste des avertissements
     */
    public function warningList(WarningRepository $warningRepository): Response
    {
        try {
            return $this->render('warning/warning_index.html.twig', [
                'warnings' => $warningRepository->findAll(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'chargement des avertissements', 'app_moderation_dashboard');
        }
    }

    /**
     * Affiche le tableau de bord des avertissements
     */
    public function warningDashboard(WarningRepository $warningRepository): Response
    {
        try {
            $warnings = $warningRepository->findAll();
            $dashboardData = $this->prepareDashboardData($warnings);

            return $this->render('warning/dashboard.html.twig', $dashboardData);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'chargement du tableau de bord', 'app_moderation_dashboard');
        }
    }

    /**
     * Crée un nouvel avertissement
     */
    public function newWarning(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $warning = $this->createWarning();
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
            return $this->handleModerationException($e, 'création de l\'avertissement', 'app_warning_index');
        }
    }

    /**
     * Affiche les détails d'un avertissement
     */
    public function showWarning(Warning $warning, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->markWarningAsViewed($warning, $entityManager);

            return $this->render('warning/show.html.twig', [
                'warning' => $warning,
            ]);
        } catch (\Exception $e) {
            return $this->handleModerationException($e, 'affichage de l\'avertissement', 'app_warning_index');
        }
    }

    /**
     * Modifier un avertissement existant
     */
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
            return $this->handleModerationException($e, 'modification de l\'avertissement', 'app_warning_index');
        }
    }

    /**
     * Supprimer un avertissement
     */
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
            return $this->handleModerationException($e, 'suppression de l\'avertissement', 'app_warning_index');
        }
    }

    /**
     * Crée un nouvel objet Warning avec les valeurs par défaut
     */
    private function createWarning(?User $user = null): Warning
    {
        $warning = new Warning();
        if ($user) {
            $warning->setUser($user);
        }
        $warning->setModerator($this->getUser());
        $warning->setCreatedAtValue(new \DateTime());
        $warning->setViewed(false);
        
        return $warning;
    }

    /**
     * Marque un avertissement comme lu
     */
    private function markWarningAsViewed(Warning $warning, EntityManagerInterface $entityManager): void
    {
        if (!$warning->isViewed()) {
            $warning->setViewed(true);
            $entityManager->flush();
        }
    }

    /**
     * Prépare les données pour le tableau de bord
     */
    private function prepareDashboardData(array $warnings): array
    {
        // Filtrer les avertissements non lus
        $unviewedWarnings = array_filter($warnings, function ($warning) {
            return !$warning->isViewed();
        });

        // Récupérer les avertissements récents triés par date
        usort($warnings, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        $recentWarnings = array_slice($warnings, 0, 10);

        return [
            'warnings' => $warnings,
            'unviewedWarnings' => $unviewedWarnings,
            'recentWarnings' => $recentWarnings,
            'totalWarnings' => count($warnings),
            'unviewedCount' => count($unviewedWarnings)
        ];
    }
}