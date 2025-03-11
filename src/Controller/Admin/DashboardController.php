<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/Admin/DashboardController.php

namespace App\Controller\Admin;

use App\Entity\Photo;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Contrôleur pour le tableau de bord administrateur
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    /**
     * Page principale du tableau de bord
     * 
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route("/admin", name: "admin")]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est connecté et a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès refusé: vous n\'avez pas les permissions nécessaires.');

        // Afficher la page d'administration avec quelques statistiques de base
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * Configuration du tableau de bord
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('FootBook Admin')
            ->setFaviconPath('favicon.ico');
    }

    /**
     * Configuration des éléments du menu
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Photos', 'fas fa-image', Photo::class);
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'app_home');
    }
}