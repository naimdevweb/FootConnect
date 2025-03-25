<?php

namespace App\Services;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class SearchService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Recherche des utilisateurs par pseudo
     */
    public function searchUsers(string $query): array
    {
        try {
            $users = $this->userRepository->searchByPseudo($query);
            
            // Filtrer les utilisateurs pour exclure les administrateurs et modérateurs
            return array_filter($users, function ($user) {
                return !in_array('ROLE_ADMIN', $user->getRoles()) 
                    && !in_array('ROLE_MODERATOR', $user->getRoles());
            });
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche d\'utilisateurs: ' . $e->getMessage());
            return [];
        }
    }
}