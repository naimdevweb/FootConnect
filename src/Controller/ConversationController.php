<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Factory\ConversationFactory;
use App\Repository\ConversationRepository;
use App\Services\TopicService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method User|null getUser()
 */
final class ConversationController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationFactory $factory,
        private readonly TopicService $topicService,
        private readonly Discovery $discovery,
        private readonly Authorization $authorization,
        private readonly \Psr\Log\LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Affiche une conversation entre l'utilisateur actuel et un destinataire
     * 
     * @param string $recipient Pseudo du destinataire
     * @param Request $request Requête HTTP
     * @return Response Réponse HTTP
     */
    #[Route('/conversation/{recipient}', name: 'conversation.show')]
    public function show(string $recipient, Request $request): Response
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder aux conversations');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupération de l'utilisateur destinataire par son pseudo
        $recipientUser = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $recipient]);
        
        // Vérification que le destinataire existe
        if (!$recipientUser) {
            $this->addFlash('error', 'Destinataire introuvable');
            return $this->redirectToRoute('app_home');
        }
        
        // Recherche d'une conversation existante entre les deux utilisateurs
        $conversation = $this->conversationRepository->findByUser($currentUser, $recipientUser);
        
        // Si aucune conversation n'existe, en créer une nouvelle
        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->addUser($currentUser);
            $conversation->addUser($recipientUser);
            $this->conversationRepository->save($conversation);
        }
        
        // Générer le topic pour Mercure
        $topic = $this->topicService->forConversation($conversation);
        
        // Utiliser une URL par défaut au lieu de dépendre d'un paramètre qui pourrait ne pas exister
        $mercureHubUrl = 'https://localhost:3000/.well-known/mercure';
        
        // Tenter de récupérer l'URL depuis les paramètres s'il existe
        try {
            $hubUrl = $this->getParameter('mercure.hub_url');
            if ($hubUrl !== null) {
                $mercureHubUrl = $hubUrl;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Le paramètre mercure.hub_url n\'est pas défini. Utilisation de l\'URL par défaut.', [
                'defaultUrl' => $mercureHubUrl
            ]);
        }
        
        // Créer la réponse avec une gestion de l'URL de Mercure plus robuste
        $response = $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
            'recipient' => $recipientUser,
            'topic' => $topic,
            'mercureHubUrl' => $mercureHubUrl
        ]);
        
        return $response;
    }
}