<?php

namespace App\Entity;

use App\Repository\StatutRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatutRepository::class)]
class Statut
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'statuts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'otherUser')]
    private ?User $otherUser = null;

    #[ORM\Column]
    private ?bool $isFollowing = null;

    #[ORM\Column]
    private ?bool $isBlocked = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOtherUser(): ?User
    {
        return $this->otherUser;
    }

    public function setOtherUser(?User $otherUser): static
    {
        $this->otherUser = $otherUser;

        return $this;
    }

    public function isFollowing(): ?bool
    {
        return $this->isFollowing;
    }

    public function setIsFollowing(bool $isFollowing): static
    {
        $this->isFollowing = $isFollowing;

        return $this;
    }

    public function isBlocked(): ?bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }
}
