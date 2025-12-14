<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\Repository\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['subscription:read']],
    denormalizationContext: ['groups' => ['subscription:write']],
    operations: [
        new Get(
            uriTemplate: '/subscriptions/current',
            controller: \App\Controller\SubscriptionController::class . '::current',
            name: 'subscription_current',
            read: false
        ),
        new Get(
            uriTemplate: '/subscriptions/check-limits',
            controller: \App\Controller\SubscriptionController::class . '::checkLimits',
            name: 'subscription_check_limits',
            read: false
        ),
        new Post(
            uriTemplate: '/subscriptions/create-checkout',
            controller: \App\Controller\SubscriptionController::class . '::createCheckout',
            name: 'subscription_create_checkout',
            deserialize: false
        ),
        new Post(
            uriTemplate: '/subscriptions/customer-portal',
            controller: \App\Controller\SubscriptionController::class . '::customerPortal',
            name: 'subscription_customer_portal',
            deserialize: false
        ),
        new Post(
            uriTemplate: '/subscriptions/{id}/cancel',
            controller: \App\Controller\SubscriptionController::class . '::cancel',
            name: 'subscription_cancel'
        ),
        new Post(
            uriTemplate: '/subscriptions/{id}/change-plan',
            controller: \App\Controller\SubscriptionController::class . '::changePlan',
            name: 'subscription_change_plan'
        ),
        new GetCollection(),
        new Get(),
        new Patch(),
        new Delete(),
    ],
)]
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';

    public const BILLING_INTERVAL_MONTH = 'month';
    public const BILLING_INTERVAL_YEAR = 'year';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:read'])]
    private ?Plan $plan = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['subscription:read'])]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 50)]
    #[Groups(['subscription:read'])]
    private ?string $status = null;

    #[ORM\Column(length: 20)]
    #[Groups(['subscription:read'])]
    private ?string $billingInterval = null;

    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?float $amount = null;

    #[ORM\Column(length: 3)]
    #[Groups(['subscription:read'])]
    private ?string $currency = 'EUR';

    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'subscription')]
    private Collection $invoices;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): static
    {
        $this->plan = $plan;
        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getBillingInterval(): ?string
    {
        return $this->billingInterval;
    }

    public function setBillingInterval(string $billingInterval): static
    {
        $this->billingInterval = $billingInterval;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(\DateTimeImmutable $currentPeriodStart): static
    {
        $this->currentPeriodStart = $currentPeriodStart;
        return $this;
    }

    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(\DateTimeImmutable $currentPeriodEnd): static
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): static
    {
        $this->canceledAt = $canceledAt;
        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;
        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;
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
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setSubscription($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getSubscription() === $this) {
                $invoice->setSubscription(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING || 
               ($this->trialEndsAt && $this->trialEndsAt > new \DateTimeImmutable());
    }

    public function hasExpired(): bool
    {
        return $this->endsAt && $this->endsAt < new \DateTimeImmutable();
    }

    public function getDaysUntilExpiration(): int
    {
        if (!$this->currentPeriodEnd) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $diff = $this->currentPeriodEnd->diff($now);
        
        return $this->currentPeriodEnd > $now ? $diff->days : -$diff->days;
    }

    public function cancel(): static
    {
        $this->status = self::STATUS_CANCELED;
        $this->canceledAt = new \DateTimeImmutable();
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}