<?php

namespace App\Controller\Trait;

use App\Entity\User;
use App\Repository\WarningRepository;

/**
 * Trait de gestion des avertissements du profil
 */
trait ProfileWarningTrait
{
    /**
     * Récupère les avertissements d'un utilisateur
     */
    public function getUserWarnings(User $user, WarningRepository $warningRepository): array
    {
        $warnings = $warningRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $unviewedWarnings = $warningRepository->findBy([
            'user' => $user,
            'viewed' => false
        ], ['createdAt' => 'DESC']);
        
        return [
            'warnings' => $warnings,
            'unviewedWarnings' => $unviewedWarnings
        ];
    }
}