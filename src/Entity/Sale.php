<?php

declare(strict_types=1);

// src/Entity/Sale.php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiFilter;

#[ApiResource(
    normalizationContext: ['groups' => ['sale:read']],
    denormalizationContext: ['groups' => ['sale:write']],
    order: ['createdAt' => 'DESC'],
    forceEager: false,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 1000
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'canal.id' => 'exact',
    'canal.name' => 'partial',
    'user.id' => 'exact',
    'salesProducts.client.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(RangeFilter::class, properties: ['price', 'benefit', 'nbProduct'])]
#[ApiFilter(OrderFilter::class, properties: [
    'name', 
    'createdAt', 
    'price', 
    'benefit', 
    'nbProduct',
    'canal.name'
], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    #[Groups(['sale:read', 'sale:write'])]
    private ?SalesChannel $canal = null;

    #[ORM\OneToMany(targetEntity: SalesProduct::class, mappedBy: 'sale', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['sale:read', 'sale:write'])]
    private Collection $salesProducts;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $benefit = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $nbProduct = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    #[Groups(['sale:read', 'sale:write'])]
    private ?User $user = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $ursaf = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $expense = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $commission = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?float $time = null;

    #[ORM\Column(length: 255)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?string $name = null;

    public function __construct()
    {
        $this->salesProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCanal(): ?SalesChannel
    {
        return $this->canal;
    }

    public function setCanal(?SalesChannel $canal): static
    {
        $this->canal = $canal;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, SalesProduct>
     */
    public function getSalesProducts(): Collection
    {
        return $this->salesProducts;
    }

    public function addSalesProduct(SalesProduct $salesProduct): static
    {
        if (!$this->salesProducts->contains($salesProduct)) {
            $this->salesProducts->add($salesProduct);
            $salesProduct->setSale($this);
        }

        return $this;
    }

    public function removeSalesProduct(SalesProduct $salesProduct): static
    {
        if ($this->salesProducts->removeElement($salesProduct)) {
            if ($salesProduct->getSale() === $this) {
                $salesProduct->setSale(null);
            }
        }

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getBenefit(): ?float
    {
        return $this->benefit;
    }

    public function setBenefit(float $benefit): static
    {
        $this->benefit = $benefit;
        return $this;
    }

    public function getNbProduct(): ?float
    {
        return $this->nbProduct;
    }

    public function setNbProduct(float $nbProduct): static
    {
        $this->nbProduct = $nbProduct;
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

    public function getUrsaf(): ?float
    {
        return $this->ursaf;
    }

    public function setUrsaf(float $ursaf): static
    {
        $this->ursaf = $ursaf;
        return $this;
    }

    public function getExpense(): ?float
    {
        return $this->expense;
    }

    public function setExpense(float $expense): static
    {
        $this->expense = $expense;
        return $this;
    }

    public function getCommission(): ?float
    {
        return $this->commission;
    }

    public function setCommission(float $commission): static
    {
        $this->commission = $commission;
        return $this;
    }

    public function getTime(): ?float
    {
        return $this->time;
    }

    public function setTime(float $time): static
    {
        $this->time = $time;
        return $this;
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

    /**
     * Automatically set createdAt before persisting if not already set
     */
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    /**
     * Automatically update updatedAt before persisting or updating
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}