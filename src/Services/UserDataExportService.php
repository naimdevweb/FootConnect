<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\Statut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service pour l'export des données utilisateur au format JSON (RGPD)
 */
class UserDataExportService
{
    /**
     * Constructeur avec injection des dépendances
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly SluggerInterface $slugger,
        private readonly string $projectDir
    ) {
    }

    /**
     * Exporte toutes les données d'un utilisateur au format JSON
     * 
     * @param User $user L'utilisateur dont on veut exporter les données
     * @return string Le chemin du fichier exporté
     * @throws \Exception Si une erreur survient lors de l'export
     */
    public function generateUserDataExport(User $user): string
    {
        try {
            // Collecte des données
            $userData = $this->collectUserData($user);
            
            // Création du répertoire d'export si nécessaire
            $exportDir = $this->projectDir . '/var/exports';
            $fs = new Filesystem();
            if (!$fs->exists($exportDir)) {
                $fs->mkdir($exportDir);
            }
            
            // Génération du nom de fichier unique
            $fileName = sprintf(
                '%s_data_export_%s.json',
                $this->slugger->slug($user->getPseudo()),
                date('Y-m-d_H-i-s')
            );
            $filePath = $exportDir . '/' . $fileName;
            
            // Sérialisation et écriture
            $jsonContent = $this->serializer->serialize($userData, 'json', [
                'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ]);
            
            file_put_contents($filePath, $jsonContent);
            
            return $filePath;
        } catch (\Exception $e) {
            // Gestion des erreurs conformément aux bonnes pratiques
            throw new \Exception('Erreur lors de l\'export des données: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Collecte toutes les données personnelles d'un utilisateur
     * 
     * @param User $user L'utilisateur
     * @return array Les données structurées
     */
    private function collectUserData(User $user): array
    {
        try {
            // Informations de base
            $userData = [
                'personal_info' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'pseudo' => $user->getPseudo(),
                    'last_login' => $this->formatDateIfExists($user->getLastLoginAt()),
                    'is_verified' => $user->isVerified(),
                    'is_deleted' => $user->isDeleted(),
                    'deleted_at' => $this->formatDateIfExists($user->getDeletedAt()),
                ],
                'content' => [
                    'photos' => [],
                    'comments' => [],
                    'likes' => [],
                    'favorites' => [],
                ],
                'privacy' => [
                    'blocked_users' => [],
                    'following' => [],
                    'followers' => [],
                ],
                'messages' => []
            ];
            
            // Photos
            foreach ($user->getPhotos() as $photo) {
                $userData['content']['photos'][] = [
                    'id' => $photo->getId(),
                    'url' => $photo->getPhotoUrl(),
                    'description' => $photo->getDescription(),
                    'created_at' => $this->formatDateIfExists($photo->getCreatedAt()),
                    'likes_count' => $photo->getLikes()->count(),
                    'comments_count' => $photo->getComments()->count(),
                ];
            }
            
            // Commentaires
            foreach ($user->getCommentaires() as $comment) {
                $userData['content']['comments'][] = [
                    'id' => $comment->getId(),
                    'message' => $comment->getMessage(),
                    'created_at' => $this->formatDateIfExists($comment->getCreatedAt()),
                    'photo_id' => $comment->getPhoto()->getId(),
                    'likes_count' => $comment->getLikeCommentaires()->count(),
                ];
            }
            
            // Likes
            foreach ($user->getLikes() as $like) {
                $userData['content']['likes'][] = [
                    'photo_id' => $like->getPhoto()->getId(),
                    // La méthode getCreatedAt n'existe pas dans l'entité Like
                ];
            }
            
            // Favoris
            foreach ($user->getFavoritePosts() as $favorite) {
                try {
                    $userData['content']['favorites'][] = [
                        'photo_id' => $favorite->getPhoto()->getId(),
                        'created_at' => $this->formatDateIfExists($favorite->getCreatedAt()),
                    ];
                } catch (\Exception $e) {
                    // Si un favori cause une erreur, on continue avec les autres
                    continue;
                }
            }
            
            // Relations
            foreach ($user->getStatuts() as $statut) {
                try {
                    $otherUser = $statut->getOtherUser();
                    
                    if ($statut->isBlocked()) {
                        $userData['privacy']['blocked_users'][] = [
                            'user_id' => $otherUser->getId(),
                            'pseudo' => $otherUser->getPseudo(),
                        ];
                    }
                    
                    if ($statut->isFollowing()) {
                        $userData['privacy']['following'][] = [
                            'user_id' => $otherUser->getId(),
                            'pseudo' => $otherUser->getPseudo(),
                        ];
                    }
                } catch (\Exception $e) {
                    // Si un statut cause une erreur, on continue avec les autres
                    continue;
                }
            }
            
            try {
                // Abonnés (followers)
                $followers = $this->entityManager->getRepository(Statut::class)
                    ->findBy(['otherUser' => $user, 'isFollowing' => true]);
                
                foreach ($followers as $follower) {
                    $userData['privacy']['followers'][] = [
                        'user_id' => $follower->getUser()->getId(),
                        'pseudo' => $follower->getUser()->getPseudo(),
                    ];
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on continue sans bloquer le processus
                $userData['privacy']['error'] = 'Impossible de récupérer les abonnés';
            }
            
            // Messages privés
            foreach ($user->getMessages() as $message) {
                try {
                    $userData['messages'][] = [
                        'conversation_id' => $message->getConversation()->getId(),
                        'content' => $message->getContent(),
                        'created_at' => $this->formatDateIfExists($message->getCreatedAt()),
                    ];
                } catch (\Exception $e) {
                    // En cas d'erreur sur un message, on passe au suivant
                    continue;
                }
            }
            
            return $userData;
        } catch (\Exception $e) {
            // En cas d'erreur globale, on renvoie au moins les informations de base
            return [
                'personal_info' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'pseudo' => $user->getPseudo(),
                    'error' => 'Une erreur est survenue lors de la collecte complète des données',
                ]
            ];
        }
    }
    
    /**
     * Formate une date si elle existe
     * 
     * @param \DateTimeInterface|null $date La date à formater
     * @return string|null La date formatée ou null
     */
    private function formatDateIfExists(?\DateTimeInterface $date): ?string
    {
        return $date ? $date->format('Y-m-d H:i:s') : null;
    }
    
    /**
     * Prépare une réponse HTTP pour télécharger un fichier d'export
     * 
     * @param string $filePath Le chemin du fichier d'export
     * @return Response La réponse HTTP
     * @throws \Exception Si le fichier n'existe pas
     */
    public function createDownloadResponse(string $filePath): Response
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('Le fichier d\'export n\'existe pas');
            }
            
            $response = new Response(file_get_contents($filePath));
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la création de la réponse de téléchargement: ' . $e->getMessage(), 0, $e);
        }
    }
}