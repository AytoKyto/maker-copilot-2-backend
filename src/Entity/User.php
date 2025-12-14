<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(),
        new Post(processor: UserPasswordHasher::class, validationContext: ['groups' => ['Default', 'user:write']]),
        new Get(),
        new Get(
            uriTemplate: '/me',
            security: "is_granted('ROLE_USER')",
            provider: 'App\\State\\CurrentUserProvider'
        ),
        new Put(processor: UserPasswordHasher::class),
        new Patch(processor: UserPasswordHasher::class),
        new Delete(),
    ],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['user:write'])]
    #[Groups(['user:write'])]
    #[SerializedName('password')]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(nullable: true)]
    private ?float $urssafPourcent = null;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(nullable: true)]
    private ?int $urssafType = null;


    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'user')]
    private Collection $products;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'user')]
    private Collection $categories;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: SalesChannel::class, mappedBy: 'user')]
    private Collection $salesChannels;

    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'user')]
    private Collection $clients;

    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'user')]
    private Collection $sales;

    #[ORM\OneToMany(targetEntity: Spent::class, mappedBy: 'user')]
    private Collection $spents;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user')]
    private Collection $subscriptions;

    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'user')]
    private Collection $invoices;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?int $objectifValue = null;

    #[ORM\Column(options: ["default" => 0])]
    #[Groups(['user:read', 'user:write'])]
    private ?int $typeSubscription = null;

    #[ORM\Column(options: ["default" => 0])]
    #[Groups(['user:read', 'user:write'])]
    private ?float $abatementPourcent = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $stripeCustomerId = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->salesChannels = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->sales = new ArrayCollection();
        $this->spents = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Username getter for frontend compatibility.
     * Returns email since we use email as username.
     */
    #[Groups(['user:read'])]
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

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
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setUser($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getUser() === $this) {
                $product->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setUser($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getUser() === $this) {
                $category->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SalesChannel>
     */
    public function getSalesChannels(): Collection
    {
        return $this->salesChannels;
    }

    public function addSalesChannel(SalesChannel $salesChannel): static
    {
        if (!$this->salesChannels->contains($salesChannel)) {
            $this->salesChannels->add($salesChannel);
            $salesChannel->setUser($this);
        }

        return $this;
    }

    public function removeSalesChannel(SalesChannel $salesChannel): static
    {
        if ($this->salesChannels->removeElement($salesChannel)) {
            // set the owning side to null (unless already changed)
            if ($salesChannel->getUser() === $this) {
                $salesChannel->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setUser($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getUser() === $this) {
                $client->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sale>
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sale $sale): static
    {
        if (!$this->sales->contains($sale)) {
            $this->sales->add($sale);
            $sale->setUser($this);
        }

        return $this;
    }

    public function removeSale(Sale $sale): static
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getUser() === $this) {
                $sale->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Spent>
     */
    public function getSpents(): Collection
    {
        return $this->spents;
    }

    public function addSpent(Spent $spent): static
    {
        if (!$this->spents->contains($spent)) {
            $this->spents->add($spent);
            $spent->setUser($this);
        }

        return $this;
    }

    public function removeSpent(Spent $spent): static
    {
        if ($this->spents->removeElement($spent)) {
            // set the owning side to null (unless already changed)
            if ($spent->getUser() === $this) {
                $spent->setUser(null);
            }
        }

        return $this;
    }

    public function getUrssafPourcent(): ?float
    {
        return $this->urssafPourcent;
    }

    public function setUrssafPourcent(?float $urssafPourcent): static
    {
        $this->urssafPourcent = $urssafPourcent;

        return $this;
    }

    public function getUrssafType(): ?int
    {
        return $this->urssafType;
    }

    public function setUrssafType(?int $urssafType): static
    {
        $this->urssafType = $urssafType;

        return $this;
    }

    public function getObjectifValue(): ?int
    {
        return $this->objectifValue;
    }

    public function setObjectifValue(?int $objectifValue): static
    {
        $this->objectifValue = $objectifValue;

        return $this;
    }

    // Different type of sub 

    /*
    Abonnement Gratuit : 0 : 0€
    Abonnement Basic : 1 : 5€ / 55€
    Abonnement Full : 2 : 10€ / 100€
    Abonnement Basic Testeur : 3 : 3,5€ / 35€ 
    Abonnement Full Testeur : 4 : 6,5 / 65€
    Abonnement Full Gratuit : 5 : 0€

    */

    public function getTypeSubscription(): ?int
    {
        return $this->typeSubscription;
    }

    public function setTypeSubscription(int $typeSubscription): static
    {
        $this->typeSubscription = $typeSubscription;

        return $this;
    }

    public function getAbatementPourcent(): ?float
    {
        return $this->abatementPourcent;
    }

    public function setAbatementPourcent(?float $abatementPourcent): static
    {
        $this->abatementPourcent = $abatementPourcent;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;
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
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

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
            $invoice->setUser($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getUser() === $this) {
                $invoice->setUser(null);
            }
        }

        return $this;
    }

    public function getActiveSubscription(): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isActive()) {
                return $subscription;
            }
        }
        return null;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->getActiveSubscription() !== null;
    }

    public function getCurrentPlan(): ?Plan
    {
        $activeSubscription = $this->getActiveSubscription();
        return $activeSubscription ? $activeSubscription->getPlan() : null;
    }

    public function canCreateProducts(): bool
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) {
            return false; // No plan = no access
        }

        if ($plan->isUnlimited()) {
            return true;
        }

        return $this->products->count() < $plan->getMaxProducts();
    }

    public function hasDetailedReports(): bool
    {
        $plan = $this->getCurrentPlan();
        return $plan && $plan->hasDetailedReports();
    }

    public function getRolesBySubscription(): array
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) {
            return ['ROLE_USER']; // Default role
        }

        return array_merge(['ROLE_USER'], $plan->getRoles());
    }
}
