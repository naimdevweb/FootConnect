<?php

namespace App\Controller;

use App\Controller\Trait\ProfileBlockTrait;
use App\Controller\Trait\ProfileFollowTrait;
use App\Controller\Trait\ProfilePhotoTrait;
use App\Controller\Trait\ProfileUpdateTrait;
use App\Controller\Trait\ProfileWarningTrait;
use App\Entity\Statut;
use App\Entity\User;
use App\Form\ProfilePictureType;
use App\Services\ProfilCountService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\WarningRepository;

class ProfilController extends AbstractController
{
    use ProfilePhotoTrait;
    use ProfileFollowTrait;
    use ProfileBlockTrait;
    use ProfileUpdateTrait;
    use ProfileWarningTrait;

    private ProfilCountService $profilCountService;
    private EntityManagerInterface $entityManager;
    private WarningRepository $warningRepository;
    private LoggerInterface $logger;
    private string $uploadDirectory;

    public function __construct(
        ProfilCountService $profilCountService, 
        EntityManagerInterface $entityManager,
        WarningRepository $warningRepository,
        LoggerInterface $logger
    ) {
        $this->profilCountService = $profilCountService;
        $this->entityManager = $entityManager;
        $this->warningRepository = $warningRepository;
        $this->logger = $logger;
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

    /**
     * Permet à l'utilisateur de modifier sa photo de profil
     */
    #[Route('/profile/picture', name: 'app_profile_picture')]
    #[IsGranted('ROLE_USER')]
    public function editProfilePicture(
        Request $request,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Création et gestion du formulaire
        $form = $this->createForm(ProfilePictureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $profilePictureFile */
            $profilePictureFile = $form->get('profilePicture')->getData();

            if ($profilePictureFile) {
                try {
                    $this->logger->info('Début du traitement de la photo de profil', [
                        'user_id' => $user->getId(),
                        'file_size' => $profilePictureFile->getSize(),
                        'file_mime' => $profilePictureFile->getMimeType(),
                    ]);
                    
                    // Vérification et configuration du répertoire d'upload
                    $this->configureUploadDirectory();
                    
                    // Test d'écriture pour vérifier les permissions
                    $this->testDirectoryWritePermissions();
                    
                    // Traitement de l'image
                    $newFilename = $this->handleImageUpload($profilePictureFile, $slugger, $user);
                    
                    // Mise à jour de l'entité User et enregistrement en BDD
                    $this->updateUserProfilePicture($user, $newFilename);
                    
                    // Message de succès
                    $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');
                    
                } catch (FileException $e) {
                    $this->logger->error('Erreur lors de l\'upload du fichier: ' . $e->getMessage(), [
                        'exception' => $e,
                        'user_id' => $user->getId()
                    ]);
                    $this->addFlash('error', 'Une erreur est survenue lors du téléversement de la photo: ' . $e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->error('Erreur générale: ' . $e->getMessage(), [
                        'exception' => $e,
                        'trace' => $e->getTraceAsString(),
                        'user_id' => $user->getId()
                    ]);
                    $this->addFlash('error', 'Une erreur inattendue est survenue: ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    
    
   
/**
 * Affiche le profil d'un utilisateur spécifié
 * 
 * @param User $user Utilisateur dont on affiche le profil
 * @return Response Réponse HTTP
 */
#[Route('/profil/user/{pseudo}', name: 'app_profil_user')]
public function userProfile(string $pseudo): Response
{
    $user = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
    
    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable.');
        return $this->redirectToRoute('app_profil');
    }
    
    $warningData = ['warnings' => [], 'unviewedWarnings' => []];
    $isFollowing = false;
    $isBlocked = false;
    
    // Récupérer l'utilisateur connecté
    $currentUser = $this->getUser();
    
    // Vérifier si l'utilisateur est connecté
    if ($currentUser) {
        // Récupérer le statut entre les deux utilisateurs
        $statut = $this->entityManager->getRepository(Statut::class)->findOneBy([
            'user' => $currentUser,
            'otherUser' => $user
        ]);
        
        // Définir les variables isFollowing et isBlocked pour le template
        if ($statut) {
            $isFollowing = (bool)$statut->isFollowing();
            $isBlocked = (bool)$statut->isBlocked();
        }
    }
    
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
        'unviewedWarnings' => $warningData['unviewedWarnings'],
        'isFollowing' => $isFollowing,
        'isBlocked' => $isBlocked
    ]);
}



    #[Route('/update', name: 'app_profil_edit')]
    public function update(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_login');
        }
        
        // Traitement des données du profil
        $updateData = $this->handleProfileUpdate($request, $user, $this->entityManager, $passwordHasher);
        
        // Traitement spécifique de la photo de profil
        $profilePicture = $request->files->get('profilePicture');
        
        if ($profilePicture) {
            try {
                // Configuration du répertoire d'upload
                $this->configureUploadDirectory();
                
                // Traitement de l'image
                $newFilename = $this->handleImageUpload($profilePicture, $slugger, $user);
                
                // Mise à jour de l'entité User avec la nouvelle photo
                $this->updateUserProfilePicture($user, $newFilename);
                
                $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');
                
                return $this->redirectToRoute('app_profil');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du traitement de la photo de profil: ' . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Une erreur est survenue lors du téléversement de la photo: ' . $e->getMessage());
            }
        }
        
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
    #[Route("/profil/{pseudo}/followers", name: "app_followers")]
    public function showFollowers(string $pseudo): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_profil');
        }
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $followersData = $this->prepareFollowersData($user, $this->entityManager);
        
        return $this->render('profil/abonnes.html.twig', array_merge(
            ['user' => $user],
            $followersData
        ));
    }

    #[Route("/profil/{pseudo}/following", name: "app_following")]
    public function showFollowing(string $pseudo): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_profil');
        }
        
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
   #[Route("/profil/{pseudo}/blocked", name: "app_blocked_users")]
public function showBlockedUsers(string $pseudo): Response
{
    $user = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable.');
        return $this->redirectToRoute('app_profil');
    }
    
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    $blockedData = $this->prepareBlockedUsersData($user, $this->entityManager);
    
    return $this->render('profil/abonnes.html.twig', array_merge(
        ['user' => $user],
        $blockedData
    ));
}

    /**
     * Configure et vérifie le répertoire d'upload
     */
   // Modifiez la méthode configureUploadDirectory
private function configureUploadDirectory(): void
{
    try {
        // Définir directement le chemin du répertoire
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDirectory = $projectDir . '/public/uploads/profile_pictures';
        
        $this->logger->info("Utilisation du chemin d'upload: {$uploadDirectory}");
        
        // S'assurer que le répertoire existe avec les bonnes permissions
        if (!file_exists($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
            $this->logger->info("Répertoire créé: {$uploadDirectory}");
        }

        // Vérification des droits d'écriture
        if (!is_writable($uploadDirectory)) {
            // Sur Windows, ajuster les permissions du dossier
            if (PHP_OS_FAMILY === 'Windows') {
                chmod($uploadDirectory, 0755);
            }
            
            if (!is_writable($uploadDirectory)) {
                throw new FileException("Le répertoire n'est pas accessible en écriture: {$uploadDirectory}");
            }
        }
        
        $this->uploadDirectory = $uploadDirectory;
    } catch (\Exception $e) {
        $this->logger->error("Erreur lors de la configuration du répertoire: " . $e->getMessage(), [
            'exception' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
    
    /**
     * S'assure que le répertoire existe, le crée sinon
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!file_exists($directory)) {
            $this->logger->info("Création du répertoire: {$directory}");
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new FileException("Impossible de créer le répertoire d'upload: {$directory}");
            }
            $this->logger->info("Répertoire créé avec succès");
        }
        
        if (!is_writable($directory)) {
            $permissions = substr(sprintf('%o', fileperms($directory)), -4);
            $this->logger->error("Le répertoire n'est pas accessible en écriture", [
                'directory' => $directory,
                'permissions' => $permissions,
                'owner' => fileowner($directory),
                'group' => filegroup($directory)
            ]);
            throw new FileException("Le répertoire n'est pas accessible en écriture: {$directory}");
        }
        
        $this->logger->info("Répertoire vérifié et accessible: {$directory}");
    }
    
    /**
     * Test d'écriture pour vérifier les permissions
     */
    private function testDirectoryWritePermissions(): void
    {
        $testFile = $this->uploadDirectory . '/test_write_permission.txt';
        $testContent = 'Test écriture - ' . date('Y-m-d H:i:s');
        
        try {
            // Tentative d'écriture
            $result = file_put_contents($testFile, $testContent);
            if ($result === false) {
                $this->logger->error("Échec d'écriture du fichier test", [
                    'testFile' => $testFile
                ]);
                throw new FileException("Impossible d'écrire dans le répertoire: {$this->uploadDirectory}");
            }
            
            // Vérification que le fichier a bien été créé
            if (!file_exists($testFile)) {
                $this->logger->error("Fichier test non trouvé après écriture");
                throw new FileException("Le fichier test n'a pas été créé malgré une écriture réussie");
            }
            
            // Suppression du fichier test
            unlink($testFile);
            $this->logger->info("Test d'écriture réussi dans le répertoire");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors du test d'écriture: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Traite l'upload de l'image et retourne le nouveau nom de fichier
     */
    private function handleImageUpload(UploadedFile $file, SluggerInterface $slugger, User $user): string
    {
        try {
            // Log détaillé du fichier reçu
            $this->logger->info("Traitement de l'image uploadée", [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            // Génération d'un nom de fichier unique et sécurisé
            $newFilename = $this->generateUniqueFilename($file, $slugger);
            $this->logger->info("Nouveau nom de fichier généré: {$newFilename}");
            
            // Suppression de l'ancienne photo si elle existe
            $this->removeOldProfilePicture($user);
            
            // Chemin de destination
            $targetPath = $this->uploadDirectory . '/' . $newFilename;
            $this->logger->info("Tentative de déplacement du fichier vers: {$targetPath}");
            
            // Upload du nouveau fichier
            $file->move($this->uploadDirectory, $newFilename);
            
            // Vérification que le fichier a bien été créé
            if (!file_exists($targetPath)) {
                $this->logger->error("Le fichier n'a pas été créé à l'emplacement attendu", [
                    'targetPath' => $targetPath
                ]);
                throw new FileException("Le fichier n'a pas été créé à l'emplacement attendu: {$targetPath}");
            }
            
            $this->logger->info("Fichier uploadé avec succès: {$newFilename}", [
                'file_size' => filesize($targetPath),
                'file_perms' => substr(sprintf('%o', fileperms($targetPath)), -4)
            ]);
            
            return $newFilename;
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'upload du fichier", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Génère un nom de fichier unique et sécurisé
     */
    private function generateUniqueFilename(UploadedFile $file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?? 'bin';
        
        return $safeFilename . '-' . uniqid() . '.' . $extension;
    }
    
    /**
     * Supprime l'ancienne photo de profil si elle existe
     */
    private function removeOldProfilePicture(User $user): void
    {
        $oldProfilePicture = $user->getProfilePicture();
        
        if ($oldProfilePicture) {
            $oldProfilePicturePath = $this->uploadDirectory . '/' . $oldProfilePicture;
            
            if (file_exists($oldProfilePicturePath)) {
                $this->logger->info("Suppression de l'ancienne photo: {$oldProfilePicturePath}");
                unlink($oldProfilePicturePath);
            } else {
                $this->logger->warning("L'ancienne photo n'a pas été trouvée: {$oldProfilePicturePath}");
            }
        }
    }
    
    /**
     * Met à jour l'entité User avec la nouvelle photo et persiste les changements
     */
    private function updateUserProfilePicture(User $user, string $filename): void
    {
        try {
            // Mise à jour de l'entité
            $user->setProfilePicture($filename);
            
            // Enregistrement en base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->logger->info("Profil utilisateur mis à jour avec la photo: {$filename}", [
                'user_id' => $user->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la mise à jour du profil utilisateur", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }
}