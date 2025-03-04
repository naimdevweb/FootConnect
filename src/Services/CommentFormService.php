<?php

namespace App\Services;

use App\Entity\Announce;
use App\Entity\Comment;
use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Form\CommentaireType;
use App\Form\CommentType;
use App\Interfaces\CommentFormServiceInterface; // Ensure this interface exists in the specified namespace
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class CommentFormService implements CommentFormServiceInterface
{
    private FormFactoryInterface $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function createCommentForm(Photo $announce): FormInterface
    {
        $comment = new Commentaire();
        $comment->setphoto($announce);

        return $this->formFactory->create(CommentaireType::class, $comment, [
            'photo_id' => $announce->getId()
        ]);
    }
}