<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/UserWarningController.php

namespace App\Controller;

use App\Entity\Warning;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour les fonctionnalités d'avertissement accessibles aux utilisateurs
 */
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class UserWarningController extends AbstractController
{
    
    /**
     * Permet à un utilisateur de voir le détail d'un avertissement qui le concerne
     */
    #[Route('/warnings/view/{id}', name: 'app_warning_user_view')]
    public function viewWarning(Warning $warning, EntityManagerInterface $entityManager): Response
    {
        try {
            // Vérifier que l'utilisateur est bien le destinataire de l'avertissement ou un modérateur
            if ($this->getUser() !== $warning->getUser() && 
                !$this->isGranted('ROLE_MODERATEUR')) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cet avertissement.');
            }
            
            // Marquer l'avertissement comme lu si l'utilisateur est le destinataire
            if (!$warning->isViewed() && $this->getUser() === $warning->getUser()) {
                $warning->setViewed(true);
                $entityManager->flush();
            }
            
            return $this->render('warning/user_view.html.twig', [
                'warning' => $warning,
                'isOwnWarning' => $this->getUser() === $warning->getUser()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            /** @var User */
            $user = $this->getUser();
            return $this->redirectToRoute('app_profil', ['pseudo' => $user->getPseudo()]);
        }
    }
}