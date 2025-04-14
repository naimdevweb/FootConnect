<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\IsTrue;

class AccountDeletionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirmation', TextType::class, [
                'label' => 'Pour confirmer la suppression, veuillez écrire "SUPPRIMER" ci-dessous',
                'mapped' => false,
                'attr' => [
                    'class' => 'shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md',
                ],
                'constraints' => [
                    new EqualTo([
                        'value' => 'SUPPRIMER',
                        'message' => 'Veuillez écrire exactement "SUPPRIMER" pour confirmer.',
                    ]),
                ],
            ])
            ->add('understand', CheckboxType::class, [
                'label' => 'Je comprends que cette action est irréversible après 30 jours et que toutes mes données seront définitivement supprimées.',
                'mapped' => false,
                'attr' => [
                    'class' => 'h-4 w-4 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 border-gray-300 dark:border-gray-600 rounded transition-colors duration-200',
                ],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez confirmer que vous comprenez les conséquences de cette action.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}