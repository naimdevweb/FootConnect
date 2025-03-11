<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Security/LoginSuccessHandler.php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Gère la redirection après une connexion réussie
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * Constructeur avec injection de dépendance
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Méthode appelée automatiquement après une connexion réussie
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        
        // Vérifier si l'utilisateur est un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // Rediriger vers le dashboard admin
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        }
        
        // Sinon rediriger vers la page d'accueil
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}