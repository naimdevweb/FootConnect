<?php

namespace App\Controller;

use App\DTO\CreateMessage;
use App\Entity\Message;
use App\Factory\MessageFactory;
use App\Repository\ConversationRepository;
use App\Services\TopicService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

final class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageFactory $factory,
        private readonly ConversationRepository $conversationRepository,
        private readonly TopicService $topicService,
        private readonly HubInterface $hub
    ) {}

    #[Route('/messages', name: 'message.create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateMessage $payload): Response
    {
        $conversation = $this->conversationRepository->find($payload->conversationId);
        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $message = $this->factory->create(
            conversation: $conversation,
            author: $this->getUser(),
            content: $payload->content
        );

        // Données à envoyer via Mercure
        $data = [
            'author' => $message->getAuthor()->getId(),
            'content' => $message->getContent(),
        ];

        $update = new Update(
            topics: $this->topicService->getTopicUrl($conversation),
            data: json_encode($data),
            private: true
        );

        $this->hub->publish($update); // Publier via Mercure

        return new Response('', Response::HTTP_CREATED);
    }
}
