<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use App\Repository\PaymentMethodRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['payment_method:read']],
    denormalizationContext: ['groups' => ['payment_method:write']],
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Delete(),
    ],
)]
#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PaymentMethod
{
    public const TYPE_CARD = 'card';
    public const TYPE_SEPA_DEBIT = 'sepa_debit';
    public const TYPE_PAYPAL = 'paypal';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['payment_method:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['payment_method:read'])]
    private ?string $stripePaymentMethodId = null;

    #[ORM\Column(length: 50)]
    #[Groups(['payment_method:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 4, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $cardLast4 = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $cardBrand = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?int $cardExpMonth = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?int $cardExpYear = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $billingName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $billingEmail = null;

    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?bool $isDefault = false;

    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $stripeData = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
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

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(string $stripePaymentMethodId): static
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCardLast4(): ?string
    {
        return $this->cardLast4;
    }

    public function setCardLast4(?string $cardLast4): static
    {
        $this->cardLast4 = $cardLast4;
        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): static
    {
        $this->cardBrand = $cardBrand;
        return $this;
    }

    public function getCardExpMonth(): ?int
    {
        return $this->cardExpMonth;
    }

    public function setCardExpMonth(?int $cardExpMonth): static
    {
        $this->cardExpMonth = $cardExpMonth;
        return $this;
    }

    public function getCardExpYear(): ?int
    {
        return $this->cardExpYear;
    }

    public function setCardExpYear(?int $cardExpYear): static
    {
        $this->cardExpYear = $cardExpYear;
        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): static
    {
        $this->billingName = $billingName;
        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): static
    {
        $this->billingEmail = $billingEmail;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
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

    public function getStripeData(): ?array
    {
        return $this->stripeData;
    }

    public function setStripeData(?array $stripeData): static
    {
        $this->stripeData = $stripeData;
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

    public function getDisplayName(): string
    {
        if ($this->type === self::TYPE_CARD) {
            return sprintf('%s ending in %s', 
                ucfirst($this->cardBrand), 
                $this->cardLast4
            );
        }

        return ucfirst($this->type);
    }

    public function isExpired(): bool
    {
        if (!$this->cardExpYear || !$this->cardExpMonth) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $expiry = \DateTimeImmutable::createFromFormat('Y-m-d', 
            sprintf('%d-%02d-01', $this->cardExpYear, $this->cardExpMonth)
        )->modify('last day of this month');

        return $expiry < $now;
    }

    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        if (!$this->cardExpYear || !$this->cardExpMonth) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $expiry = \DateTimeImmutable::createFromFormat('Y-m-d', 
            sprintf('%d-%02d-01', $this->cardExpYear, $this->cardExpMonth)
        )->modify('last day of this month');

        $threshold = $now->modify(sprintf('+%d days', $daysThreshold));

        return $expiry <= $threshold && $expiry >= $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}