<?php

namespace App\Controller;

use App\Controller\Trait\ProfileBlockTrait;
use App\Controller\Trait\ProfileFollowTrait;
use App\Controller\Trait\ProfilePhotoTrait;
use App\Controller\Trait\ProfileUpdateTrait;
use App\Controller\Trait\ProfileWarningTrait;
use App\Entity\User;
use App\Services\ProfilCountService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\WarningRepository;

class ProfilController extends AbstractController
{
    use ProfilePhotoTrait;
    use ProfileFollowTrait;
    use ProfileBlockTrait;
    use ProfileUpdateTrait;
    use ProfileWarningTrait;

    private $profilCountService;
    private $entityManager;
    private $warningRepository;

    public function __construct(
        ProfilCountService $profilCountService, 
        EntityManagerInterface $entityManager,
        WarningRepository $warningRepository
    ) {
        $this->profilCountService = $profilCountService;
        $this->entityManager = $entityManager;
        $this->warningRepository = $warningRepository;
    }

    #[Route('/profil', name: 'app_profil')]
    public function profile(Request $request, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à votre profil.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les avertissements
        $warningData = $this->getUserWarnings($user, $this->warningRepository);
        
        // Gérer l'upload de photo
        $photoData = $this->handlePhotoUpload($request, $slugger, $user, $this->entityManager);
        
        // Rediriger si la photo a été ajoutée avec succès
        if ($photoData['success']) {
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/index.html.twig', [
            'form' => $photoData['form'],
            'photos' => $user->getPhotos(),
            'user' => $user,
            'followersCount' => $this->profilCountService->countFollowers($user),
            'followingCount' => $this->profilCountService->countFollowing($user),
            'warnings' => $warningData['warnings'],
            'unviewedWarnings' => $warningData['unviewedWarnings']
        ]);
    }

    #[Route('/profil/user/{id}', name: 'app_profil_user')]
    public function userProfile(User $user): Response
    {
        $warningData = ['warnings' => [], 'unviewedWarnings' => []];
        
        // Pour les modérateurs, on permet de voir les avertissements des autres utilisateurs
        if ($this->isGranted('ROLE_MODERATOR') || $this->isGranted('ROLE_ADMIN')) {
            $warningData = $this->getUserWarnings($user, $this->warningRepository);
        }

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'photos' => $user->getPhotos(),
            'followersCount' => $this->profilCountService->countFollowers($user),
            'followingCount' => $this->profilCountService->countFollowing($user),
            'warnings' => $warningData['warnings'],
            'unviewedWarnings' => $warningData['unviewedWarnings']
        ]);
    }

    #[Route('/update', name: 'app_profil_edit')]
    public function update(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_login');
        }
        
        $updateData = $this->handleProfileUpdate($request, $user, $this->entityManager, $passwordHasher);
        
        if ($updateData['success']) {
            return $this->redirectToRoute('app_profil');
        }
        
        return $this->render('profil/update.html.twig', [
            'form' => $updateData['form'],
            'user' => $user
        ]);
    }

    /**
     * Affiche les abonnés de l'utilisateur spécifié
     */
    #[Route("/profil/{id}/followers", name: "app_followers")]
    public function showFollowers(User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $followersData = $this->prepareFollowersData($user, $this->entityManager);
        
        return $this->render('profil/abonnes.html.twig', array_merge(
            ['user' => $user],
            $followersData
        ));
    }

    #[Route("/profil/{id}/following", name: "app_following")]
    public function showFollowing(User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $followingData = $this->prepareFollowingData($user, $this->entityManager);
        
        return $this->render('profil/abonnes.html.twig', array_merge(
            ['user' => $user],
            $followingData
        ));
    }

    /**
     * Affiche la liste des utilisateurs bloqués
     */
    #[Route("/profil/{id}/blocked", name: "app_blocked_users")]
    public function showBlockedUsers(User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $blockedData = $this->prepareBlockedUsersData($user, $this->entityManager);
        
        return $this->render('profil/abonnes.html.twig', array_merge(
            ['user' => $user],
            $blockedData
        ));
    }
}