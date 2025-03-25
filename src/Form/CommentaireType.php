<?php


namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('message', TextareaType::class, [
            'constraints' => [
                new NotBlank([
                    'message' => 'Le commentaire ne peut pas être vide'
                ])
            ],
            'label' => false
        ])
        ->add('photoId', HiddenType::class, [
            'mapped' => false,
            'data' => $options['photo_id']
        ]);
}

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Commentaire::class,
        'photo_id' => null,
    ]);
    }
}