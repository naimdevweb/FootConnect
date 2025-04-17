<?php

namespace App\Services;

use App\Entity\Conversation;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion des topics pour Mercure
 */
class TopicService
{
    /**
     * Constructeur du service
     * 
     * @param RequestStack $requestStack Stack de requêtes
     */
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Génère un topic pour une conversation spécifique
     * 
     * @param Conversation $conversation La conversation pour laquelle générer un topic
     * @return string Le topic généré
     */
    public function forConversation(Conversation $conversation): string
    {
        return "conversation/{$conversation->getId()}";
    }

    /**
     * Récupère l'URL complète du topic pour une conversation
     * 
     * @param Conversation $conversation La conversation
     * @return string L'URL complète du topic
     */
    public function getTopicUrl(Conversation $conversation): string
    {
        return "{$this->getServerUrl()}/conversation/{$conversation->getId()}";
    }

    /**
     * Récupère l'URL de base du serveur
     * 
     * @return string L'URL du serveur (schéma + hôte + port)
     */
    private function getServerUrl(): string
    {
        $request = $this->requestStack->getMainRequest();

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $portUrl = $port ? ":$port" : '';

        return $scheme . '://' . $host . $portUrl;
    }
}