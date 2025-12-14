<?php

declare(strict_types=1);
// src/Entity/Price.php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['price:read', 'sale:read']],
    denormalizationContext: ['groups' => ['price:write']],
    paginationEnabled: false
)]
#[ORM\Entity(repositoryClass: PriceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Price
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $benefit = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    #[Groups(['price:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    #[Groups(['price:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'prices', cascade: ['persist'])]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\OneToMany(targetEntity: SalesProduct::class, mappedBy: 'price')]
    private Collection $salesProducts;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $ursaf = null;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $expense = null;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $commission = null;

    #[ORM\Column]
    #[Groups(['price:read', 'price:write', 'product:read', 'product:write', 'sale:read'])]
    private ?float $time = null;

    #[ORM\Column(options: ["default" => false], nullable: true)]
    #[Groups(['product:read', 'product:write', 'sale:read'])]
    private ?bool $isArchived = null;

    public function __construct()
    {
        $this->salesProducts = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
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

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getBenefit(): ?float
    {
        return $this->benefit;
    }

    public function setBenefit(float $benefit): self
    {
        $this->benefit = $benefit;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
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
            $salesProduct->setPrice($this);
        }

        return $this;
    }

    public function removeSalesProduct(SalesProduct $salesProduct): static
    {
        if ($this->salesProducts->removeElement($salesProduct)) {
            // set the owning side to null (unless already changed)
            if ($salesProduct->getPrice() === $this) {
                $salesProduct->setPrice(null);
            }
        }

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

    public function isIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(?bool $isArchived): static
    {
        $this->isArchived = $isArchived;

        return $this;
    }
}
