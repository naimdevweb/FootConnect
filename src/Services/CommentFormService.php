<?php

namespace App\Services;

use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Form\CommentaireType;
use App\Interfaces\CommentFormServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class CommentFormService implements CommentFormServiceInterface
{
    private FormFactoryInterface $formFactory;
    private EntityManagerInterface $entityManager;

    public function __construct(FormFactoryInterface $formFactory, EntityManagerInterface $entityManager)
    {
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
    }

    public function createCommentForm(Photo $photo): FormInterface
    {
        $comment = new Commentaire();
        $comment->setPhoto($photo);

        return $this->formFactory->create(CommentaireType::class, $comment, [
            'photo_id' => $photo->getId()
        ]);
    }

    public function handleCommentForm(FormInterface $form): void
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
        }
    }
}