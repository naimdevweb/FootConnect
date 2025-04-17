<?php

namespace App\Controller;

use App\Entity\Statut;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatutController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Gère le suivi, le blocage et le déblocage des utilisateurs
     *
     * @param string $pseudo Pseudo de l'utilisateur concerné
     * @param string $action Action à effectuer (follow, unfollow, block, unblock)
     * @return Response Réponse HTTP
     */
    #[Route('/statut/{pseudo}/{action}', name: 'app_statut', methods: ['GET', 'POST'])]
    public function handleUserStatus(string $pseudo, string $action): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }
        
        // Rechercher l'autre utilisateur par son pseudo
        $otherUser = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
        
        if (!$otherUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_profil');
        }
        
        // Récupérer le statut existant ou créer un nouveau
        $statut = $this->entityManager->getRepository(Statut::class)->findOneBy([
            'user' => $currentUser,
            'otherUser' => $otherUser
        ]);
        
        if (!$statut) {
            $statut = new Statut();
            $statut->setUser($currentUser);
            $statut->setOtherUser($otherUser);
            $statut->setIsFollowing(0);
            $statut->setIsBlocked(0);
        }
        
        // Traiter les différentes actions
        switch ($action) {
            case 'follow':
                // Vérifier si l'utilisateur est bloqué
                if ($statut->isBlocked()) {
                    $this->addFlash('error', 'Vous devez d\'abord débloquer cet utilisateur pour pouvoir le suivre.');
                } else {
                    $statut->setIsFollowing(1);
                    $this->entityManager->persist($statut);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Vous suivez maintenant cet utilisateur.');
                }
                break;
                
            case 'unfollow':
                $statut->setIsFollowing(0);
                $this->entityManager->persist($statut);
                $this->entityManager->flush();
                $this->addFlash('success', 'Vous ne suivez plus cet utilisateur.');
                break;
                
            case 'block':
                $statut->setIsBlocked(1);
                $statut->setIsFollowing(0); // Ne peut pas suivre un utilisateur bloqué
                $this->entityManager->persist($statut);
                $this->entityManager->flush();
                $this->addFlash('success', 'Utilisateur bloqué avec succès.');
                break;
                
            case 'unblock':
                $statut->setIsBlocked(0);
                $this->entityManager->persist($statut);
                $this->entityManager->flush();
                $this->addFlash('success', 'Utilisateur débloqué avec succès.');
                break;
                
            default:
                $this->addFlash('error', 'Action non reconnue.');
                break;
        }

        // Redirection vers le profil de l'utilisateur
        return $this->redirectToRoute('app_profil_user', ['pseudo' => $pseudo]);
    }
}