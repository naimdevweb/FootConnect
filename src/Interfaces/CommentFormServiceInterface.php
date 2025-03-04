<?php

namespace App\Interfaces;

use Symfony\Component\Form\FormInterface;
use App\Entity\Announce;
use App\Entity\Photo;

interface CommentFormServiceInterface
{
    public function createCommentForm(Photo $announce): FormInterface;
}