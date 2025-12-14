<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SalesChannelRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    paginationEnabled: false,
    normalizationContext: ['groups' => ['sale:read', 'sales_channel:read']],
    denormalizationContext: ['groups' => ['sale:write', 'sales_channel:write']],
)]
#[ORM\Entity(repositoryClass: SalesChannelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SalesChannel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read', 'sales_channel:read', 'sales_channel:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['sale:read', 'sales_channel:read', 'sale:write', 'sales_channel:write'])]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'canal')]
    private Collection $sales;

    #[Groups(['sales_channel:read'])]
    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['sales_channel:read'])]
    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['sale:read', 'sales_channel:read', 'sale:write', 'sales_channel:write'])]
    #[ORM\ManyToOne(inversedBy: 'salesChannels')]
    private ?User $user = null;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
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

    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setCanal($this);
        }

        return $this;
    }

    public function removeSale(Sale $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getCanal() === $this) {
                $sale->setCanal(null);
            }
        }

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

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
}
