<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Report
{
    const TYPE_POST = 'post';
    const TYPE_COMMENT = 'comment';
    const TYPE_USER = 'user';
    
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reporter = null;
    
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::TYPE_POST, self::TYPE_COMMENT, self::TYPE_USER])]
    private string $reportType;
    
    #[ORM\Column(type: 'integer')]
    private int $reportedItemId;
    
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $reason;
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $moderator = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $moderatorNote = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $resolvedAt = null;
    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(User $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    public function setReportType(string $reportType): self
    {
        $this->reportType = $reportType;

        return $this;
    }

    public function getReportedItemId(): int
    {
        return $this->reportedItemId;
    }

    public function setReportedItemId(int $reportedItemId): self
    {
        $this->reportedItemId = $reportedItemId;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }

    public function getModeratorNote(): ?string
    {
        return $this->moderatorNote;
    }

    public function setModeratorNote(?string $moderatorNote): self
    {
        $this->moderatorNote = $moderatorNote;

        return $this;
    }
    
    public function getResolvedAt(): ?\DateTime
    {
        return $this->resolvedAt;
    }
    
    public function setResolvedAt(?\DateTime $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        
        return $this;
    }
}