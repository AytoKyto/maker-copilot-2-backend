<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['plan:read']],
    operations: [
        new GetCollection(),
        new Get(),
    ],
)]
#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    public const STARTER = 'starter';
    public const PRO = 'pro';
    public const UNLIMITED = 'unlimited';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plan:read', 'subscription:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['plan:read', 'subscription:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['plan:read', 'subscription:read'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Groups(['plan:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?float $monthlyPrice = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?float $yearlyPrice = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['plan:read'])]
    private ?string $stripeMonthlyPriceId = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['plan:read'])]
    private ?string $stripeYearlyPriceId = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plan:read'])]
    private ?int $maxProducts = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?bool $hasDetailedReports = false;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?bool $isPopular = false;

    #[ORM\Column(type: 'json')]
    #[Groups(['plan:read'])]
    private array $features = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['plan:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'plan')]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMonthlyPrice(): ?float
    {
        return $this->monthlyPrice;
    }

    public function setMonthlyPrice(float $monthlyPrice): static
    {
        $this->monthlyPrice = $monthlyPrice;
        return $this;
    }

    public function getYearlyPrice(): ?float
    {
        return $this->yearlyPrice;
    }

    public function setYearlyPrice(float $yearlyPrice): static
    {
        $this->yearlyPrice = $yearlyPrice;
        return $this;
    }

    public function getStripeMonthlyPriceId(): ?string
    {
        return $this->stripeMonthlyPriceId;
    }

    public function setStripeMonthlyPriceId(?string $stripeMonthlyPriceId): static
    {
        $this->stripeMonthlyPriceId = $stripeMonthlyPriceId;
        return $this;
    }

    public function getStripeYearlyPriceId(): ?string
    {
        return $this->stripeYearlyPriceId;
    }

    public function setStripeYearlyPriceId(?string $stripeYearlyPriceId): static
    {
        $this->stripeYearlyPriceId = $stripeYearlyPriceId;
        return $this;
    }

    public function getMaxProducts(): ?int
    {
        return $this->maxProducts;
    }

    public function setMaxProducts(?int $maxProducts): static
    {
        $this->maxProducts = $maxProducts;
        return $this;
    }

    public function hasDetailedReports(): ?bool
    {
        return $this->hasDetailedReports;
    }

    public function setHasDetailedReports(bool $hasDetailedReports): static
    {
        $this->hasDetailedReports = $hasDetailedReports;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isPopular(): ?bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): static
    {
        $this->isPopular = $isPopular;
        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): static
    {
        $this->features = $features;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getPlan() === $this) {
                $subscription->setPlan(null);
            }
        }

        return $this;
    }

    public function getYearlySavings(): float
    {
        return ($this->monthlyPrice * 12) - $this->yearlyPrice;
    }

    public function getYearlySavingsPercentage(): float
    {
        if ($this->monthlyPrice <= 0) {
            return 0;
        }
        
        return round(($this->getYearlySavings() / ($this->monthlyPrice * 12)) * 100, 1);
    }

    public function isUnlimited(): bool
    {
        return $this->maxProducts === null;
    }

    public function isFree(): bool
    {
        return $this->monthlyPrice <= 0;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}