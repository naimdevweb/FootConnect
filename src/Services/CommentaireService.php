<?php

namespace App\Services;

use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Repository\PhotoRepository;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentaireService extends AbstractController
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatutRepository $statutRepository,
    ) {}

    /**
     * Traite la soumission des commentaires
     */
    public function handleCommentSubmission(Request $request, ?User $currentUser): ?Response
    {
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération préliminaire de la photo
        $photoId = $this->extractPhotoId($request);
        
        if (!$photoId) {
            $this->addFlash('error', 'ID de photo manquant');
            return $this->redirectToRoute('app_home');
        }
        
        // Récupérer l'objet photo
        $photo = $this->photoRepository->find($photoId);
        if (!$photo) {
            $this->addFlash('error', 'Photo inexistante');
            return $this->redirectToRoute('app_home');
        }
        
        // Vérifier si l'utilisateur est bloqué
        if ($this->isUserBlocked($photo->getUser(), $currentUser)) {
            $this->addFlash('error', 'Vous ne pouvez pas commenter les publications de cet utilisateur');
            return $this->redirectToRoute('app_home');
        }
        
        $comment = $this->createCommentaire($currentUser, $photo);
        $form = $this->createFormForCommentaire($comment, $photo, $request);
        
        if (!$form->isSubmitted()) {
            return null;
        }
        
        if (!$form->isValid()) {
            $this->logValidationErrors($form);
            return null;
        }
        
        return $this->saveAndRespond($comment, $currentUser, $request);
    }

    /**
     * Extrait l'ID de la photo depuis la requête
     */
    private function extractPhotoId(Request $request): ?int
    {
        if ($request->request->has('photo_id')) {
            return (int) $request->request->get('photo_id');
        } 
        
        if ($request->request->has('commentaire') && 
            isset($request->request->get('commentaire')['photoId'])) {
            return (int) $request->request->get('commentaire')['photoId'];
        }
        
        return null;
    }

    /**
     * Vérifie si l'utilisateur est bloqué
     */
    private function isUserBlocked(User $photoUser, User $currentUser): bool
    {
        $statut = $this->statutRepository->findOneBy([
            'user' => $currentUser,
            'otherUser' => $photoUser
        ]);

        return $statut && $statut->isBlocked();
    }

    /**
     * Crée un nouvel objet commentaire
     */
    private function createCommentaire(User $user, Photo $photo): Commentaire
    {
        $comment = new Commentaire();
        $comment->setUser($user);
        $comment->setPhoto($photo);
        $comment->setCreatedAt(new \DateTimeImmutable());
        
        return $comment;
    }
    
    /**
     * Crée le formulaire pour un commentaire
     */
    private function createFormForCommentaire(Commentaire $comment, Photo $photo, Request $request)
    {
        $form = $this->createForm(CommentaireType::class, $comment, [
            'photo_id' => $photo->getId()
        ]);
        $form->handleRequest($request);
        
        return $form;
    }
    
    /**
     * Enregistre les erreurs de validation dans le journal
     */
    private function logValidationErrors($form): void
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        error_log('Erreurs de validation: ' . implode(', ', $errors));
        
        foreach ($form as $fieldName => $field) {
            if (count($field->getErrors()) > 0) {
                $fieldErrors = [];
                foreach ($field->getErrors() as $error) {
                    $fieldErrors[] = $error->getMessage();
                }
                error_log("Erreurs pour le champ $fieldName: " . implode(', ', $fieldErrors));
            }
        }
        
        $this->addFlash('error', 'Formulaire invalide : ' . implode(', ', $errors));
    }
    
    /**
     * Sauvegarde le commentaire et prépare la réponse
     */
    private function saveAndRespond(Commentaire $comment, User $user, Request $request): Response
    {
        try {
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a été ajouté');
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'comment' => [
                        'id' => $comment->getId(),
                        'message' => $comment->getMessage(),
                        'userPseudo' => $user->getPseudo(),
                        'userInitial' => strtoupper(substr($user->getPseudo(), 0, 1)),
                        'createdAt' => $comment->getCreatedAt()->format('H:i')
                    ]
                ]);
            }
            
            return $this->redirectToRoute('app_home');
            
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout du commentaire: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du commentaire');
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de l\'ajout du commentaire'
                ], 500);
            }
            
            return $this->redirectToRoute('app_home');
        }
    }
}