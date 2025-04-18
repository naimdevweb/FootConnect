<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type("\DateTimeImmutable")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Type("string", message: 'Le message doit être une chaîne de caractères')]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Photo $photo = null;

    /**
     * @var Collection<int, LikeCommentaire>
     */
    #[ORM\OneToMany(targetEntity: LikeCommentaire::class, mappedBy: 'commentaire')]
    private Collection $likeCommentaires;

    public function __construct()
    {
        $this->likeCommentaires = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->message;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function setPhoto(?Photo $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection<int, LikeCommentaire>
     */
    public function getLikeCommentaires(): Collection
    {
        return $this->likeCommentaires;
    }

    public function addLikeCommentaire(LikeCommentaire $likeCommentaire): static
    {
        if (!$this->likeCommentaires->contains($likeCommentaire)) {
            $this->likeCommentaires->add($likeCommentaire);
            $likeCommentaire->setCommentaire($this);
        }

        return $this;
    }

    public function removeLikeCommentaire(LikeCommentaire $likeCommentaire): static
    {
        if ($this->likeCommentaires->removeElement($likeCommentaire)) {
            // set the owning side to null (unless already changed)
            if ($likeCommentaire->getCommentaire() === $this) {
                $likeCommentaire->setCommentaire(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si un commentaire est liké par un utilisateur donné
     */
    public function isLikedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        foreach ($this->likeCommentaires as $like) {
            if ($like->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Récupère le nombre de likes pour ce commentaire
     */
    public function getLikesCount(): int
    {
        return $this->likeCommentaires->count();
    }
}