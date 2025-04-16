<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Form\PhotoFormType;
use App\Form\PostType;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

class PhotoController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?string $uploadDirectory = null;
    
    /**
     * Constructeur avec injection de l'EntityManager et du Logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Initialise et retourne le répertoire d'upload des photos
     */
    private function getUploadDirectory(): string
    {
        if ($this->uploadDirectory === null) {
            $this->uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
            
            // Création du répertoire s'il n'existe pas
            if (!is_dir($this->uploadDirectory)) {
                $this->logger->info('Création du répertoire : ' . $this->uploadDirectory);
                mkdir($this->uploadDirectory, 0777, true);
            }
            
            // Vérification des permissions
            if (!is_writable($this->uploadDirectory)) {
                chmod($this->uploadDirectory, 0777);
            }
        }
        
        return $this->uploadDirectory;
    }

    /**
     * Affiche toutes les photos de l'utilisateur connecté
     */
    #[Route('/profil/photos', name: 'app_profil_photos')]
    public function index(PhotoRepository $photoRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
        
        $photos = $photoRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        return $this->render('photo/index.html.twig', [
            'photos' => $photos
        ]);
    }
    
    /**
     * Création d'une nouvelle photo
     */
    // #[Route('/profil/photo/new', name: 'app_profil_photo_new')]
    // #[Route('/photo/new', name: 'app_photo_new')]
    #[Route('/post/create', name: 'app_post_create')]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter une photo.');
            return $this->redirectToRoute('app_login');
        }
        
        $photo = new Photo();
        $photo->setUser($user);
        $photo->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Récupération du fichier image
                $photoFile = $form->get('photoFile')->getData();
                
                if ($photoFile) {
                    $this->logger->info('Fichier photo reçu', [
                        'file_name' => $photoFile->getClientOriginalName(),
                        'file_size' => $photoFile->getSize(),
                        'mime_type' => $photoFile->getMimeType()
                    ]);
                    
                    // Traitement du nom de fichier sécurisé
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();
                    
                    // Obtenir le répertoire d'upload
                    $uploadDir = $this->getUploadDirectory();
                    
                    // Déplacer le fichier
                    $this->logger->info('Tentative de déplacement du fichier vers : ' . $uploadDir . '/' . $newFilename);
                    $photoFile->move($uploadDir, $newFilename);
                    
                    // Vérification de la création du fichier
                    if (file_exists($uploadDir . '/' . $newFilename)) {
                        $this->logger->info('Fichier créé avec succès');
                        
                        // Mettre à jour l'entité avec le nom du fichier
                        $photo->setPhotoUrl($newFilename);
                        
                        // Enregistrement en base de données
                        $this->entityManager->persist($photo);
                        $this->entityManager->flush();
                        
                        $this->addFlash('success', 'Votre publication a été créée avec succès !');
                        return $this->redirectToRoute('app_home');
                    } else {
                        throw new FileException('Le fichier n\'a pas été correctement enregistré');
                    }
                } else {
                    $this->addFlash('error', 'Veuillez sélectionner une image.');
                }
            } catch (FileException $e) {
                $this->logger->error('Erreur lors du téléchargement du fichier: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Un problème est survenu lors du téléchargement de votre photo: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error('Erreur inattendue: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Une erreur inattendue est survenue: ' . $e->getMessage());
            }
        }
        
        // Déterminer quel template utiliser en fonction de la route
        $routeName = $request->attributes->get('_route');
        if ($routeName === 'app_post_create') {
            return $this->render('photo/create_post.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ]);
        }
        
        // Template par défaut
        return $this->render('photo/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * Modification d'une photo existante
     */
    #[Route('/photo/edit/{id}', name: 'app_edit_photo')]
    #[Route('/profil/photo/{id}/edit', name: 'app_profil_photo_edit')]
    #[Route('/photo/{id}/edit', name: 'app_photo_edit')]
    public function edit(Request $request, SluggerInterface $slugger, Photo $photo): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de la photo
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette photo.');
        }

        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photoFile')->getData();
                
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    // Obtenir le répertoire d'upload
                    $uploadDir = $this->getUploadDirectory();

                    // Supprimer l'ancienne photo si besoin
                    $oldPhotoPath = $uploadDir . '/' . $photo->getPhotoUrl();
                    if (file_exists($oldPhotoPath) && is_file($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }

                    // Déplacer le fichier vers le bon répertoire
                    $photoFile->move($uploadDir, $newFilename);

                    $photo->setPhotoUrl($newFilename);
                }

                $photo->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                $this->addFlash('success', 'Photo modifiée avec succès.');
                return $this->redirectToRoute('app_profil');
            } catch (FileException $e) {
                $this->logger->error('Erreur lors de la modification de la photo: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Un problème est survenu lors de la modification de votre photo: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error('Erreur inattendue: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Une erreur inattendue est survenue: ' . $e->getMessage());
            }
        }

        return $this->render('photo/edit.html.twig', [
            'form' => $form->createView(),
            'photo' => $photo,
        ]);
    }

    /**
     * Suppression d'une photo
     */
    #[Route('/photo/delete/{id}', name: 'app_delete_photo')]
    #[Route('/profil/photo/{id}/delete', name: 'app_profil_photo_delete')]
    #[Route('/photo/{id}/delete', name: 'app_photo_delete')]
    public function delete(Photo $photo): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de la photo
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette photo.');
        }

        try {
            // Supprimer le fichier physique
            $uploadDir = $this->getUploadDirectory();
            $photoPath = $uploadDir . '/' . $photo->getPhotoUrl();
            
            if (file_exists($photoPath) && is_file($photoPath)) {
                unlink($photoPath);
            }

            $this->entityManager->remove($photo);
            $this->entityManager->flush();

            $this->addFlash('success', 'Photo supprimée avec succès.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de la photo: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            $this->addFlash('error', 'Un problème est survenu lors de la suppression de la photo: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_profil');
    }
}