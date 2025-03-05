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
        $user = $this->getUser();

        $otheruser = $entityManager->getRepository(User::class)->find($id);
        if ($suivi == 'suivre') {

            // regarder si ya pas déja un statut
            $statut = $entityManager->getRepository(Statut::class)->findOneBy([
                    'user' => $user,
                    'otherUser' => $otheruser
                ]);
            if ($statut) {
                // Regarder si l'utilisateur est bloqué 
                if ($statut->IsBlocked() == 1) {
                    // Ajouter message flash pour dire nn
                    return $this->redirectToRoute('app_profil_user', ['id' => $id]);
                } else {
                    $isFollowing = $statut->IsFollowing();

                    if ($isFollowing == 1) {
                        $statut->setIsFollowing(0);
                    } else {
                        $statut->setIsFollowing(1);
                    }
                }
            } else {

                $statut = new Statut();
                $statut->setUser($user);
                $statut->setOtherUser($otheruser);
                $statut->setIsFollowing(1);
                $statut->setIsBlocked(0);
            }
            $entityManager->persist($statut);
            $entityManager->flush();
            
        } else if ($suivi == 'bloquer') {
            $statut = $entityManager->getRepository(Statut::class)->findOneBy([
            'user' => $user,
            'otherUser' => $otheruser
            ]);

            if ($statut) {
            $statut->setIsBlocked(1);
            $statut->setIsFollowing(0);
            } else {
            $statut = new Statut();
            $statut->setUser($user);
            $statut->setOtherUser($otheruser);
            $statut->setIsFollowing(0);
            $statut->setIsBlocked(1);
            }

            $entityManager->persist($statut);
            $entityManager->flush();
        }

        //     $statut = new Statut();
        //     $statut->setUser($user);
        //     $statut->setOtherUser($otheruser);
        //     $statut->setIsFollowing(1);
        //     $statut->setIsBlocked(0);
        //     $entityManager->persist($statut);
        //     $entityManager->flush();
        // }
        // else if($suivi == 'neplussuivre'){
        //     $statut = new Statut();
        //     $statut->setUser($user);
        //     $statut->setOtherUser($otheruser);
        //     $statut->setIsFollowing(0);
        //     $statut->setIsBlocked(0);
        //     $entityManager->persist($statut);
        //     $entityManager->flush();
        // }


        return $this->redirectToRoute('app_profil_user', ['id' => $id]);
    }
}
