<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Form\PhotoFormType;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    /**
     * Constructeur avec injection de l'EntityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Affiche toutes les photos de l'utilisateur connecté
     */
    #[Route('/profil/photos', name: 'app_profil_photos')]
    public function index(PhotoRepository $photoRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }
        
        $photos = $photoRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        return $this->render('photo/index.html.twig', [
            'photos' => $photos
        ]);
    }
    
    /**
     * Création d'une nouvelle photo
     */
    #[Route('/profil/photo/new', name: 'app_profil_photo_new')]
    #[Route('/photo/new', name: 'app_photo_new')]
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
                    
                    $this->addFlash('success', 'Votre photo a été ajoutée avec succès!');
                    return $this->redirectToRoute('app_profil');
                }
            } catch (FileException $e) {
                $this->addFlash('error', 'Un problème est survenu lors du téléchargement de votre photo.');
            }
        }
        
        return $this->render('photo/new.html.twig', [
            'form' => $form->createView()
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

                    // Déplacer le fichier
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );

                    // Supprimer l'ancienne photo si besoin
                    $oldPhotoPath = $this->getParameter('photos_directory') . '/' . $photo->getPhotoUrl();
                    if (file_exists($oldPhotoPath) && is_file($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }

                    $photo->setPhotoUrl($newFilename);
                }

                $photo->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                $this->addFlash('success', 'Photo modifiée avec succès.');
                return $this->redirectToRoute('app_profil');
            } catch (FileException $e) {
                $this->addFlash('error', 'Un problème est survenu lors de la modification de votre photo.');
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
            $photoPath = $this->getParameter('photos_directory') . '/' . $photo->getPhotoUrl();
            if (file_exists($photoPath) && is_file($photoPath)) {
                unlink($photoPath);
            }

            $this->entityManager->remove($photo);
            $this->entityManager->flush();

            $this->addFlash('success', 'Photo supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Un problème est survenu lors de la suppression de la photo.');
        }

        return $this->redirectToRoute('app_profil');
    }
}