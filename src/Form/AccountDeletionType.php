<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\IsTrue;

/**
 * Formulaire de suppression de compte utilisateur
 */
class AccountDeletionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirmation', TextType::class, [
                'label' => 'Pour confirmer la suppression, saisissez "SUPPRIMER" en majuscules',
                'constraints' => [
                    new EqualTo([
                        'value' => 'SUPPRIMER',
                        'message' => 'Veuillez saisir exactement "SUPPRIMER" pour confirmer.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'SUPPRIMER',
                ],
            ])
            ->add('understand', CheckboxType::class, [
                'label' => 'Je comprends que cette action est irréversible et que toutes mes données seront définitivement supprimées',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez confirmer que vous comprenez les conséquences de cette action.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}