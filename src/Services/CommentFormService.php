<?php

namespace App\Services;

use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\CommentaireType;
use Symfony\Component\Form\FormFactoryInterface;

class CommentFormService
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {}
    
    /**
     * Crée un formulaire de commentaire pour une photo
     */
    public function createCommentForm(Photo $photo)
    {
        $comment = new Commentaire();
        $comment->setPhoto($photo);
        
        return $this->formFactory->create(CommentaireType::class, $comment, [
            'photo_id' => $photo->getId()
        ]);
    }
    
    /**
     * Crée des formulaires de commentaires pour plusieurs photos
     */
    public function createPhotoCommentForms(array $photos, ?User $currentUser): array
    {
        $commentForms = [];

        if (!$currentUser) {
            return $commentForms;
        }

        foreach ($photos as $photo) {
            $commentForm = $this->createCommentForm($photo);
            $commentForms[$photo->getId()] = $commentForm->createView();
        }

        return $commentForms;
    }
}