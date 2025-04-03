<?php

namespace App\Controller\Trait;

use App\Entity\User;
use App\Form\UpdateUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Trait de gestion de la mise à jour du profil
 */
trait ProfileUpdateTrait
{
    /**
     * Gère la mise à jour des données du profil
     */
    public function handleProfileUpdate(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): array {
        $form = $this->createForm(UpdateUserType::class, $user);
        $form->handleRequest($request);
        
        $success = false;
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gestion du changement de mot de passe
                $plainPassword = $form->get('plainPassword')->getData();
                $emailConfirmation = $form->get('emailConfirmation')->getData();
                
                if ($plainPassword && $emailConfirmation === $user->getEmail()) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
                $success = true;
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre profil.');
            }
        }
        
        return [
            'form' => $form->createView(),
            'success' => $success
        ];
    }
}