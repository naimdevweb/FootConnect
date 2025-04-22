<?php

namespace App\Controller;

use App\DTO\CreateMessage;
use App\Entity\Conversation;
use App\Factory\MessageFactory;
use App\Repository\ConversationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MessageController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageFactory $factory
    )
    {
    }

    #[Route('/messages', name: 'message.create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $conversationId = $request->request->get('conversationId');
        $content = $request->request->get('content');
        
        if (!$conversationId || !$content) {
            return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }
        
        $conversation = $this->conversationRepository->find($conversationId);
        
        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $message = $this->factory->create(
            conversation: $conversation,
            author: $this->getUser(),
            content: $content
        );

        // Renvoyer le message nouvellement créé en HTML
        return $this->render('message/content.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/messages/get/{conversationId}', name: 'message.get', methods: ['GET'])]
    public function getMessages(int $conversationId, Request $request): Response
    {
        $conversation = $this->conversationRepository->find($conversationId);

        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur fait partie de la conversation
        $currentUser = $this->getUser();
        if (!$conversation->getUsers()->contains($currentUser)) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer l'ID du dernier message connu par le client (si fourni)
        $lastMessageId = $request->query->get('lastMessageId', 0);

        // Récupérer uniquement les nouveaux messages
        $newMessages = [];
        foreach ($conversation->getMessages() as $message) {
            if ($message->getId() > $lastMessageId) {
                $newMessages[] = $message;
            }
        }

        // Renvoyer le HTML des nouveaux messages
        return $this->render('conversation/messages.html.twig', [
            'conversation' => $conversation,
            'newMessages' => $newMessages,
        ]);
    }
}