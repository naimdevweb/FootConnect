<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountDeletionType;
use App\Services\DataRetentionService;
use App\Services\UserDataExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/data')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserDataController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DataRetentionService $dataRetentionService,
        private readonly UserDataExportService $userDataExportService
    ) {}

    /**
     * Affiche la page de gestion des données personnelles
     */
    #[Route('/manage', name: 'app_user_data_manage')]
    public function manageUserData(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Formulaire de suppression de compte
        $deletionForm = $this->createForm(AccountDeletionType::class);
        $deletionForm->handleRequest($request);

        if ($deletionForm->isSubmitted() && $deletionForm->isValid()) {
            try {
                // Marquer le compte comme supprimé au lieu de le supprimer physiquement
                $user->setIsDeleted(true);
                $user->setDeletedAt(new \DateTime());
                $this->entityManager->flush();

                // Déconnecter l'utilisateur
                $this->addFlash('success', 'Votre compte a été désactivé. Vos données seront définitivement supprimées après 30 jours.');
                return $this->redirectToRoute('app_logout');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte : ' . $e->getMessage());
            }
        }

        // Politiques de rétention des données
        $retentionPolicies = $this->dataRetentionService->getRetentionPolicies();

        return $this->render('user/data_management.html.twig', [
            'user' => $user,
            'deletionForm' => $deletionForm->createView(),
            'retentionPolicies' => $retentionPolicies
        ]);
    }

    /**
     * Exporte les données utilisateur au format JSON
     */
    #[Route('/export', name: 'app_user_data_export')]
    public function exportUserData(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour exporter vos données.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $filePath = $this->userDataExportService->generateUserDataExport($user);
            return $this->userDataExportService->createDownloadResponse($filePath);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'export de vos données : ' . $e->getMessage());
            return $this->redirectToRoute('app_user_data_manage');
        }
    }

    /**
     * Annule la suppression d'un compte si la demande est faite pendant la période de grâce
     */
    #[Route('/cancel-deletion', name: 'app_user_data_cancel_deletion')]
    public function cancelDeletion(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isDeleted()) {
            $user->setIsDeleted(false);
            $user->setDeletedAt(null);
            $this->entityManager->flush();
            $this->addFlash('success', 'La suppression de votre compte a été annulée.');
        } else {
            $this->addFlash('info', 'Votre compte n\'est pas en cours de suppression.');
        }

        return $this->redirectToRoute('app_user_data_manage');
    }

    /**
     * Affiche les informations sur la conservation des données
     */
    #[Route('/retention-info', name: 'app_user_data_retention_info')]
    public function retentionInfo(): Response
    {
        $retentionPolicies = $this->dataRetentionService->getRetentionPolicies();
        
        return $this->render('user/data_retention.html.twig', [
            'retentionPolicies' => $retentionPolicies
        ]);
    }
}