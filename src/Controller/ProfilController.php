<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Photo;
use App\Form\PhotoFormType; // Créez ce formulaire pour une seule photo
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function profile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Créer une nouvelle photo (vide) pour le formulaire
        $photo = new Photo();
        $photo->setUser($user);
        
        // Créer un formulaire pour une seule nouvelle photo
        $form = $this->createForm(PhotoFormType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier photo téléchargé
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
                
                // Ajouter la nouvelle photo à l'utilisateur
                $entityManager->persist($photo);
                $entityManager->flush();

                $this->addFlash('success', 'Votre photo a été ajoutée avec succès.');
                
                return $this->redirectToRoute('app_profil');
            }
        }

        return $this->render('profil/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    

/**
 * Affiche le profil d'un utilisateur spécifique
 */
#[Route('/profil/user/{id}', name: 'app_profil_user')]
public function userProfile(User $user): Response
{
    return $this->render('profil/user.html.twig', [
        'user' => $user,
        'photos' => $user->getPhotos(),
    ]);
}
}