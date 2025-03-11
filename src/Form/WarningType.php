<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Form/WarningType.php

namespace App\Form;

use App\Entity\Warning;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour la création d'un avertissement
 */
class WarningType extends AbstractType
{
    /**
     * Construction du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif',
                'choices' => [
                    'Contenu inapproprié' => 'inappropriate_content',
                    'Hors sujet (non-football)' => 'off_topic',
                    'Langage inapproprié' => 'inappropriate_language',
                    'Autre' => 'other'
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => [
                    'rows' => 5,
                    'class' => 'block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                ]
            ]);
    }

    /**
     * Configuration des options du formulaire
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Warning::class,
        ]);
    }
}