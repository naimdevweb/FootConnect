<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Photo;
use App\Form\PhotoFormType;
use App\Entity\Follow;
use App\Entity\Statut;
use App\Form\UpdateUserType;
use App\Interfaces\UpdateProfilInterface;
use App\Services\ProfilCountService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilController extends AbstractController
{
    private $profilCountService;
    private $entityManager;

    public function __construct(ProfilCountService $profilCountService, EntityManagerInterface $entityManager)
    {
        $this->profilCountService = $profilCountService;
        $this->entityManager = $entityManager;
    }

    #[Route('/profil', name: 'app_profil')]
    public function profile(Request $request, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Créer une nouvelle photo
        $photo = new Photo();
        $photo->setUser($user);
        
        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();
            
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );

                $photo->setPhotoUrl($newFilename);
                
                $this->entityManager->persist($photo);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre photo a été ajoutée avec succès.');
                
                return $this->redirectToRoute('app_profil');
            }
        }

        return $this->render('profil/index.html.twig', [
            'form' => $form->createView(),
            'photos' => $user->getPhotos(),
            'user' => $user,
            'followersCount' => $this->profilCountService->countFollowers($user),
            'followingCount' => $this->profilCountService->countFollowing($user),
        ]);
    }

    #[Route('/profil/user/{id}', name: 'app_profil_user')]
    public function userProfile(User $user): Response
    {
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'photos' => $user->getPhotos(),
            
        ]);
    }


    #[Route('/update', name: 'app_profil_edit')]
    public function editProfile(
        EntityManagerInterface $entityManager,
        UpdateProfilInterface $updateProfilService,
        Request $request

    ): Response
    {
        /** 
         * @var User $user
         */
        $user = $this->getUser();

        $form = $this->createForm(UpdateUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $pseudo = $form->get('pseudo')->getData();
            $email = $form->get('email')->getData();
            
            $plainPassword = $form->get('plainPassword')->getData();
            $updateProfilService->updateProfil($user, $pseudo, $email, $plainPassword);
            $this->addFlash('success', 'User updated successfully.');
            
            return $this->redirectToRoute('app_user_update');
        }

        return $this->render('profil/update.html.twig', [
            'updateForm' => $form->createView(),
            'user' => $user,
            
        ]);
    }







    /**
     * Affiche et traite le formulaire de mise à jour du profil
     */
    #[Route('/update', name: 'app_profil_edit')]
    public function update(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_login');
        }
        
        $form = $this->createForm(UpdateUserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gestion du changement de mot de passe
                $plainPassword = $form->get('plainPassword')->getData();
                $emailConfirmation = $form->get('emailConfirmation')->getData();
                /**
                 * @var User $user
                 */
                if ($plainPassword && $emailConfirmation === $user->getEmail()) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
                return $this->redirectToRoute('app_profil');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre profil.');
            }
        }
        
        return $this->render('profil/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }


   // Ajoutez ces méthodes dans votre ProfilController existant


 /**
 * Affiche les abonnés de l'utilisateur spécifié
 */
#[Route("/profil/{id}/followers", name: "app_followers")]
public function showFollowers(User $user): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    try {
        // Récupérer tous les abonnés (statuts où otherUser est l'utilisateur actuel et isFollowing=1)
        $followers = $this->entityManager->getRepository(Statut::class)->findBy([
            'otherUser' => $user,
            'isFollowing' => 1
        ]);
        
        // Extraire les objets User (ceux qui suivent l'utilisateur actuel)
        $followerUsers = [];
        foreach ($followers as $statut) {
            if (method_exists($statut, 'getUser') && $statut->getUser() instanceof User) {
                $followerUsers[] = $statut->getUser();
            }
        }
        
        // Récupérer les statuts de l'utilisateur courant pour vérifier qui il suit
        $followingStatus = $this->entityManager->getRepository(Statut::class)->findBy([
            'user' => $this->getUser(),
            'isFollowing' => 1
        ]);
        
        return $this->render('profil/abonnes.html.twig', [
            'user' => $user,
            'users' => $followerUsers,
            'isFollowers' => true,
            'followingStatus' => $followingStatus
        ]);
    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue lors de la récupération des abonnés: ' . $e->getMessage());
        
        if ($this->getParameter('kernel.environment') === 'dev') {
            throw $e;
        }
        
        return $this->redirectToRoute('app_profil');
    }
}


#[Route("/profil/{id}/following", name: "app_following")]
public function showFollowing(User $user): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    try {
        // Récupérer tous les utilisateurs suivis
        $following = $this->entityManager->getRepository(Statut::class)->findBy([
            'user' => $user,
            'isFollowing' => 1
        ]);
        
        $followingUsers = [];
        foreach ($following as $statut) {
            if (method_exists($statut, 'getOtherUser') && $statut->getOtherUser() instanceof User) {
                $followingUsers[] = $statut->getOtherUser();
            }
        }
        
        // Récupérer les statuts de l'utilisateur courant pour vérifier qui il suit
        $followingStatus = $this->entityManager->getRepository(Statut::class)->findBy([
            'user' => $this->getUser(),
            'isFollowing' => 1
        ]);
        
        // // Afficher les messages de debug
        // dump($followingUsers);
        // dump($followingStatus);
        
        return $this->render('profil/abonnes.html.twig', [
            'user' => $user,
            'users' => $followingUsers,
            'isFollowers' => false,
            'followingStatus' => $followingStatus
        ]);
    } catch (\Exception $e) {
        // Logger l'erreur pour le débogage
        $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
        
        // En mode dev, afficher l'erreur complète
        if ($this->getParameter('kernel.environment') === 'dev') {
            throw $e;
        }
        
        return $this->redirectToRoute('app_profil');
    }
}




/**
 * Affiche la liste des utilisateurs bloqués
 */
#[Route("/profil/{id}/blocked", name: "app_blocked_users")]
public function showBlockedUsers(User $user): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    try {
        // Récupérer tous les utilisateurs bloqués
        $blocked = $this->entityManager->getRepository(Statut::class)->findBy([
            'user' => $user,
            'isBlocked' => 1
        ]);
        
        
        $blockedUsers = [];
        foreach ($blocked as $statut) {
            if (method_exists($statut, 'getOtherUser') && $statut->getOtherUser() instanceof User) {
                $blockedUsers[] = $statut->getOtherUser();
            }
        }
        // dd($blockedUsers);
        
        return $this->render('profil/abonnes.html.twig', [
            'user' => $user,
            'users' => $blockedUsers,
             'mode' => 'blocked'
        ]);
    } catch (\Exception $e) {
        // Logger l'erreur pour le débogage
        $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
        
        // En mode dev, afficher l'erreur complète
        if ($this->getParameter('kernel.environment') === 'dev') {
            throw $e;
        }
        
        return $this->redirectToRoute('app_profil');
    }
}

}
