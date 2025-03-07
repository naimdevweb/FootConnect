<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Statut;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatutController extends AbstractController
{
    #[Route('/statut/{id}/{suivi}', name: 'app_statut', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, int $id, string $suivi): Response
    {
        try {
            $user = $this->getUser();
            $otherUser = $entityManager->getRepository(User::class)->find($id);
            
            if (!$otherUser) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_profil');
            }
            
            if ($suivi == 'suivre') {
                // Regarder si un statut existe déjà
                $statut = $entityManager->getRepository(Statut::class)->findOneBy([
                    'user' => $user,
                    'otherUser' => $otherUser
                ]);
                
                if ($statut) {
                    // Regarder si l'utilisateur est bloqué 
                    if ($statut->IsBlocked() == 1) {
                        $this->addFlash('error', 'Vous devez d\'abord débloquer cet utilisateur pour pouvoir le suivre.');
                        return $this->redirectToRoute('app_profil_user', ['id' => $id]);
                    } else {
                        $isFollowing = $statut->IsFollowing();

                        if ($isFollowing == 1) {
                            $statut->setIsFollowing(0);
                            $this->addFlash('success', 'Vous ne suivez plus cet utilisateur.');
                        } else {
                            $statut->setIsFollowing(1);
                            $this->addFlash('success', 'Vous suivez maintenant cet utilisateur.');
                        }
                    }
                } else {
                    $statut = new Statut();
                    $statut->setUser($user);
                    $statut->setOtherUser($otherUser);
                    $statut->setIsFollowing(1);
                    $statut->setIsBlocked(0);
                    $this->addFlash('success', 'Vous suivez maintenant cet utilisateur.');
                }
                
                $entityManager->persist($statut);
                $entityManager->flush();
                
            } elseif ($suivi == 'bloquer') {
                $statut = $entityManager->getRepository(Statut::class)->findOneBy([
                    'user' => $user,
                    'otherUser' => $otherUser
                ]);

                if ($statut) {
                    $statut->setIsBlocked(1);
                    $statut->setIsFollowing(0);
                } else {
                    $statut = new Statut();
                    $statut->setUser($user);
                    $statut->setOtherUser($otherUser);
                    $statut->setIsFollowing(0);
                    $statut->setIsBlocked(1);
                }

                $entityManager->persist($statut);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur bloqué avec succès.');
            } elseif ($suivi == 'debloquer') {
                // Gestion du déblocage d'utilisateur
                $statut = $entityManager->getRepository(Statut::class)->findOneBy([
                    'user' => $user,
                    'otherUser' => $otherUser
                ]);
                
                if ($statut) {
                    // Mettre à jour le statut pour débloquer l'utilisateur
                    $statut->setIsBlocked(0);
                    $entityManager->persist($statut);
                    $entityManager->flush();

                      /** @var User $user */
                    
                    $this->addFlash('success', 'Utilisateur débloqué avec succès.');
                    return $this->redirectToRoute('app_blocked_users', ['id' => $user->getId()]);
                } else {
                    $this->addFlash('error', 'Aucun statut trouvé pour cet utilisateur.');
                }
            }

            return $this->redirectToRoute('app_profil_user', ['id' => $id]);
        } catch (\Exception $e) {
            // Gestion des erreurs
            $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            return $this->redirectToRoute('app_profil');
        }
    }
}