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
        if($suivi == 'suivre'){
            $statut = new Statut();
            $statut->setUser($user);
            $statut->setOtherUser($otheruser);
            $statut->setIsFollowing(1);
            $statut->setIsBlocked(0);
            $entityManager->persist($statut);
            $entityManager->flush();
        }
        

            else if($suivi == 'bloquer'){
                $statut = new Statut();
                $statut->setUser($user);
                $statut->setOtherUser($otheruser);
                $statut->setIsFollowing(0);
                $statut->setIsBlocked(1);
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
    

        return $this->redirectToRoute('app_home');

     
    }



     
    }

