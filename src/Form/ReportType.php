<?php

namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reportType', HiddenType::class)
            ->add('reportedItemId', HiddenType::class)
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif du signalement',
                'choices' => [
                    'Contenu inapproprié' => 'inappropriate',
                    'Harcèlement' => 'harassment',
                    'Spam' => 'spam',
                    'Contenu offensant' => 'offensive',
                    'Autre' => 'other'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un motif.'
                    ])
                ],
                'attr' => [
                    'class' => 'form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200'
                ]
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Détails (facultatif)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200',
                    'placeholder' => 'Ajoutez des détails supplémentaires pour aider les modérateurs à comprendre votre signalement...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}