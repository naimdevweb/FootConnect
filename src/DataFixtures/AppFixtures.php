<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Photo;
use App\Entity\Commentaire;
use App\Entity\Like;
use App\Entity\FavoritePost;
use App\Entity\Statut;
use App\Entity\LikeCommentaire;
use App\Entity\PrivateMessage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Stocker les utilisateurs pour les relations
        $users = [];

        // Création de 10 utilisateurs
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setPseudo($faker->userName);
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER']);

            // Hachage du mot de passe
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

            $manager->persist($user);
            $users[] = $user;
        }

        // Stocker les photos pour les relations
        $photos = [];

        // Création de 15 photos
        for ($i = 0; $i < 15; $i++) {
            $photo = new Photo();
            $photo->setPhotoUrl($faker->imageUrl());
            $photo->setDescription($faker->sentence);
            $photo->setCreatedAt(new \DateTimeImmutable()); // Date actuelle pour created_at
            $photo->setUpdatedAt(new \DateTimeImmutable()); // Date actuelle pour updated_at
            $photo->setUser($faker->randomElement($users));

            $manager->persist($photo);
            $photos[] = $photo;
        }

        // Création de 20 commentaires
        $commentaires = [];
        for ($i = 0; $i < 20; $i++) {
            $commentaire = new Commentaire();
            $commentaire->setMessage($faker->sentence);
            $commentaire->setUser($faker->randomElement($users));
            $commentaire->setPhoto($faker->randomElement($photos));
            $commentaire->setCreatedAt(new \DateTimeImmutable()); // Date actuelle pour created_at

            $manager->persist($commentaire);
            $commentaires[] = $commentaire;
        }

        // Création de 30 likes sur des photos
        for ($i = 0; $i < 30; $i++) {
            $like = new Like();
            $like->setUser($faker->randomElement($users));
            $like->setPhoto($faker->randomElement($photos));

            $manager->persist($like);
        }

        // Création de 15 favoris
        for ($i = 0; $i < 15; $i++) {
            $favoritePost = new FavoritePost();
            $favoritePost->setUser($faker->randomElement($users));
            $favoritePost->setPhoto($faker->randomElement($photos));
            $favoritePost->setCreatedAt(new \DateTimeImmutable()); // Date actuelle pour created_at

            $manager->persist($favoritePost);
        }

        // Création de 10 statuts avec des relations de suivi et de blocage
        for ($i = 0; $i < 10; $i++) {
            $statut = new Statut();
            $user = $faker->randomElement($users);
            $otherUser = $faker->randomElement($users);

            // S'assurer que l'utilisateur et l'autre utilisateur sont différents
            while ($user === $otherUser) {
                $otherUser = $faker->randomElement($users);
            }

            $statut->setUser($user);
            $statut->setOtherUser($otherUser);

            // Définir isFollowing et isBlocked de manière aléatoire
            $statut->setIsFollowing($faker->boolean());
            $statut->setIsBlocked($faker->boolean());

            $manager->persist($statut);
        }

        // Création de 15 messages privés
        for ($i = 0; $i < 15; $i++) {
            $message = new PrivateMessage();
            $message->setSender($faker->randomElement($users));
            $message->setReceiver($faker->randomElement($users));
            $message->setContent($faker->text);
            $message->setCreatedAt(new \DateTimeImmutable()); // Date actuelle pour created_at

            $manager->persist($message);
        }

        // Création de 20 likes sur des commentaires
        for ($i = 0; $i < 20; $i++) {
            $likeCommentaire = new LikeCommentaire();
            $likeCommentaire->setUser($faker->randomElement($users));
            $likeCommentaire->setCommentaire($faker->randomElement($commentaires));

            $manager->persist($likeCommentaire);
        }

        $manager->flush();
    }
}

