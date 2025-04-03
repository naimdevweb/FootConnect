<?php

namespace App\Controller\Trait;

use App\Entity\Photo;
use App\Entity\User;
use App\Form\PhotoFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Trait de gestion des photos de profil
 */
trait ProfilePhotoTrait
{
    /**
     * Gère le téléchargement d'une nouvelle photo
     */
    public function handlePhotoUpload(Request $request, SluggerInterface $slugger, User $user, EntityManagerInterface $entityManager): array
    {
        $photo = new Photo();
        $photo->setUser($user);
        
        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        $success = false;

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
                    
                    $entityManager->persist($photo);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre photo a été ajoutée avec succès.');
                    $success = true;
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout de la photo: ' . $e->getMessage());
            }
        }

        return [
            'form' => $form->createView(),
            'success' => $success
        ];
    }

    /**
     * Récupère les photos d'un utilisateur
     */
    public function getUserPhotos(User $user): array
    {
        return $user->getPhotos()->toArray();
    }
}