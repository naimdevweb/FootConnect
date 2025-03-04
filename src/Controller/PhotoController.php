<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Form\PhotoFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoController extends AbstractController
{
    #[Route('/photo/edit/{id}', name: 'app_edit_photo')]
    public function edit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, Photo $photo): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de la photo
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette photo.');
        }

        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

                $photo->setPhotoUrl($newFilename);
            }

            $photo->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Photo modifiée avec succès.');

            return $this->redirectToRoute('app_profil');
        }

        return $this->render('photo/edit.html.twig', [
            'form' => $form->createView(),
            'photo' => $photo,
        ]);
    }

    #[Route('/photo/delete/{id}', name: 'app_delete_photo')]
    public function delete(EntityManagerInterface $entityManager, Photo $photo): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de la photo
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette photo.');
        }

        // Supprimer le fichier physique (facultatif)
        $photoPath = $this->getParameter('photos_directory') . '/' . $photo->getPhotoUrl();
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }

        $entityManager->remove($photo);
        $entityManager->flush();

        $this->addFlash('success', 'Photo supprimée avec succès.');

        return $this->redirectToRoute('app_profil');
    }
}