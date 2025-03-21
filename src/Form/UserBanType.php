<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Form/UserBanType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Formulaire pour bannir un utilisateur
 */
class UserBanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('banReason', TextareaType::class, [
                'label' => 'Raison du bannissement',
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500',
                    'rows' => 4
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez indiquer une raison pour le bannissement.']),
                    new Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'La raison doit faire au moins {{ limit }} caractères.',
                        'maxMessage' => 'La raison ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('banType', ChoiceType::class, [
                'label' => 'Type de bannissement',
                'required' => true,
                'choices' => [
                    'Temporaire (7 jours)' => 'temporary',
                    'Permanent' => 'permanent'
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'mt-1 block'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}