<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse email')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Warning::class, orphanRemoval: true)]
    private Collection $receivedWarnings;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Veuillez fournir une adresse email')]
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide')]
    #[Assert\Length(max: 180, maxMessage: 'L\'adresse email ne peut pas dépasser {{ limit }} caractères')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;


    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenCreatedAt = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotCompromisedPassword(message: 'Ce mot de passe a été compromis lors d\'une fuite de données. Veuillez en choisir un autre.')]
    private ?string $password = null;


    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $banned = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $banReason = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez fournir un pseudo')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le pseudo doit comporter au moins {{ limit }} caractères',
        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_.-]+$/',
        message: 'Le pseudo ne peut contenir que des lettres, chiffres, points, tirets et underscores'
    )]
    private ?string $pseudo = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'user')]
    private Collection $photos;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $commentaires;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user')]
    private Collection $likes;

    /**
     * @var Collection<int, FavoritePost>
     */
    #[ORM\OneToMany(targetEntity: FavoritePost::class, mappedBy: 'user')]
    private Collection $favoritePosts;

    /**
     * @var Collection<int, Statut>
     */
    #[ORM\OneToMany(targetEntity: Statut::class, mappedBy: 'user')]
    private Collection $statuts;

    /**
     * @var Collection<int, Statut>
     */
    #[ORM\OneToMany(targetEntity: Statut::class, mappedBy: 'otherUser')]
    private Collection $otherUser;

    /**
     * @var Collection<int, LikeCommentaire>
     */
    #[ORM\OneToMany(targetEntity: LikeCommentaire::class, mappedBy: 'user')]
    private Collection $likeCommentaires;

    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'users')]
    private Collection $conversations;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'author')]
    private Collection $messages;
    
    public function __toString(): string
    {
        return $this->pseudo ?? $this->email ?? '';
    }
    
    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->favoritePosts = new ArrayCollection();
        $this->statuts = new ArrayCollection();
        $this->otherUser = new ArrayCollection();
        $this->likeCommentaires = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->receivedWarnings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
    
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * @return Collection<int, Warning>
     */
    public function getReceivedWarnings(): Collection
    {
        return $this->receivedWarnings;
    }

    public function addReceivedWarning(Warning $warning): self
    {
        if (!$this->receivedWarnings->contains($warning)) {
            $this->receivedWarnings->add($warning);
            $warning->setUser($this);
        }

        return $this;
    }

    public function removeReceivedWarning(Warning $warning): self
    {
        if ($this->receivedWarnings->removeElement($warning)) {
            // set the owning side to null (unless already changed)
            if ($warning->getUser() === $this) {
                $warning->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUser($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUser() === $this) {
                $photo->setUser(null);
            }
        }

        return $this;
    }


    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): self
    {
        $this->banned = $banned;
        return $this;
    }

    public function getBanReason(): ?string
    {
        return $this->banReason;
    }

    public function setBanReason(?string $banReason): self
    {
        $this->banReason = $banReason;
        return $this;
    }

    public function getBannedAt(): ?\DateTimeInterface
    {
        return $this->bannedAt;
    }

    public function setBannedAt(?\DateTimeInterface $bannedAt): self
    {
        $this->bannedAt = $bannedAt;
        return $this;
    }

    /**
     * Get the value of favoritePosts
     */ 
    public function getFavoritePosts()
    {
        return $this->favoritePosts;
    }

    /**
     * Set the value of favoritePosts
     *
     * @return  self
     */ 
    public function setFavoritePosts($favoritePosts)
    {
        $this->favoritePosts = $favoritePosts;

        return $this;
    }

    /**
     * Récupère le jeton de réinitialisation de mot de passe
     */
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    /**
     * Définit le jeton de réinitialisation de mot de passe
     */
    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        
        return $this;
    }

    /**
     * Récupère la date de création du jeton de réinitialisation
     */
    public function getResetTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->resetTokenCreatedAt;
    }
    
    /**
     * Définit la date de création du jeton de réinitialisation
     */
    public function setResetTokenCreatedAt(?\DateTimeInterface $resetTokenCreatedAt): self
    {
        $this->resetTokenCreatedAt = $resetTokenCreatedAt;
        
        return $this;
    }

    /**
     * Vérifie si le jeton de réinitialisation est expiré
     */
    public function isResetTokenExpired(?int $expirationHours = 24): bool
    {
        if (!$this->resetToken || !$this->resetTokenCreatedAt) {
            return true;
        }
        
        if ($this->resetTokenCreatedAt instanceof \DateTimeInterface) {
            $resetTokenDate = $this->resetTokenCreatedAt instanceof \DateTimeImmutable 
                ? $this->resetTokenCreatedAt 
                : \DateTimeImmutable::createFromMutable($this->resetTokenCreatedAt);
            $expirationDate = $resetTokenDate->modify("+{$expirationHours} hours");
        } else {
            throw new \LogicException('Invalid resetTokenCreatedAt value.');
        }
        return new \DateTime() > $expirationDate;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        
        return $this;
    }

}