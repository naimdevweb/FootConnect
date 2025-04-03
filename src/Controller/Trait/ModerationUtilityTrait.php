<?php

namespace App\Controller\Trait;

use Symfony\Component\HttpFoundation\Response;

/**
 * Trait d'utilitaires communs pour la modération
 */
trait ModerationUtilityTrait
{
    /**
     * Gère les exceptions de manière uniforme
     */
    private function handleModerationException(\Exception $e, string $context, string $redirectRoute): Response
    {
        $this->addFlash('error', 'Une erreur est survenue lors du ' . $context . ': ' . $e->getMessage());
        return $this->redirectToRoute($redirectRoute);
    }
}