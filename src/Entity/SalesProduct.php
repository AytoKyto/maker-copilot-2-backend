<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SalesProductRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    normalizationContext: ['groups' => ['sale:read']],
    denormalizationContext: ['groups' => ['sale:write']],
    paginationEnabled: false,
)]
#[GetCollection(normalizationContext: ['groups' => 'sale:collection:get'])]
#[ORM\Entity(repositoryClass: SalesProductRepository::class)]
class SalesProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write', 'sale:collection:get'])]
    private ?int $id = null;

    #[Groups(['sale:collection:get'])]
    #[ORM\ManyToOne(inversedBy: 'salesProducts')]
    private ?Sale $sale = null;

    #[Groups(['sale:read', 'sale:write', 'sale:collection:get'])]
    #[ORM\ManyToOne(inversedBy: 'salesProducts')]
    private ?Product $product = null;

    #[Groups(['sale:read', 'sale:write', 'sale:collection:get'])]
    #[ORM\ManyToOne(inversedBy: 'salesProducts')]
    private ?Price $price = null;

    #[Groups(['sale:read', 'sale:write', 'sale:collection:get'])]
    #[ORM\ManyToOne(inversedBy: 'salesProducts')]
    private ?Client $client = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    #[Groups(['sale:read', 'sale:collection:get'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"], nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): static
    {
        $this->sale = $sale;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

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
}
