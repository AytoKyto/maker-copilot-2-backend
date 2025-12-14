<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class SubscriptionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository,
        private SubscriptionRepository $subscriptionRepository,
        private StripeService $stripeService,
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    /**
     * Créer un abonnement depuis une session Stripe
     */
    public function createSubscriptionFromStripe(string $stripeSubscriptionId, User $user, Plan $plan): Subscription
    {
        try {
            // Récupérer les détails de l'abonnement depuis Stripe
            $stripeSubscription = $this->stripeService->getStripeClient()->subscriptions->retrieve($stripeSubscriptionId);

            // Créer l'entité Subscription
            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setPlan($plan);
            $subscription->setStripeSubscriptionId($stripeSubscriptionId);
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setAmount($stripeSubscription->items->data[0]->price->unit_amount / 100); // Convertir centimes en euros
            $subscription->setCurrency(strtoupper($stripeSubscription->currency));
            
            // Déterminer l'intervalle de facturation
            $interval = $stripeSubscription->items->data[0]->price->recurring->interval;
            $subscription->setBillingInterval($interval);

            // Définir les dates
            $subscription->setCurrentPeriodStart(
                \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_start)
            );
            $subscription->setCurrentPeriodEnd(
                \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end)
            );

            // Période d'essai si applicable
            if ($stripeSubscription->trial_end) {
                $subscription->setTrialEndsAt(
                    \DateTimeImmutable::createFromFormat('U', $stripeSubscription->trial_end)
                );
            }

            // Annuler l'ancien abonnement actif s'il existe
            $currentSubscription = $user->getActiveSubscription();
            if ($currentSubscription && $currentSubscription->getId() !== $subscription->getId()) {
                $this->cancelSubscription($currentSubscription, false);
            }

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            // Mettre à jour les rôles de l'utilisateur
            $this->updateUserRoles($user);

            $this->logger->info('Abonnement créé depuis Stripe', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'stripe_subscription_id' => $stripeSubscriptionId,
                'plan_slug' => $plan->getSlug()
            ]);

            return $subscription;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'abonnement', [
                'user_id' => $user->getId(),
                'stripe_subscription_id' => $stripeSubscriptionId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de créer l\'abonnement: ' . $e->getMessage());
        }
    }

    /**
     * Annuler un abonnement
     */
    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true): void
    {
        try {
            // Annuler sur Stripe
            $this->stripeService->cancelSubscription($subscription, $atPeriodEnd);

            // Mettre à jour les rôles de l'utilisateur
            $this->updateUserRoles($subscription->getUser());

            $this->logger->info('Abonnement annulé', [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()->getId(),
                'at_period_end' => $atPeriodEnd
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'annulation de l\'abonnement', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Synchroniser un abonnement depuis Stripe
     */
    public function syncSubscriptionFromStripe(string $stripeSubscriptionId): Subscription
    {
        try {
            $stripeSubscription = $this->stripeService->getStripeClient()->subscriptions->retrieve($stripeSubscriptionId);
            
            // Trouver l'abonnement existant
            $subscription = $this->subscriptionRepository->findByStripeId($stripeSubscriptionId);
            
            if (!$subscription) {
                throw new \RuntimeException('Abonnement non trouvé');
            }

            // Mettre à jour le statut et les dates
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setCurrentPeriodStart(
                \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_start)
            );
            $subscription->setCurrentPeriodEnd(
                \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end)
            );

            // Gérer l'annulation
            if ($stripeSubscription->canceled_at) {
                $subscription->setCanceledAt(
                    \DateTimeImmutable::createFromFormat('U', $stripeSubscription->canceled_at)
                );
            }

            if ($stripeSubscription->cancel_at_period_end) {
                $subscription->setEndsAt(
                    \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end)
                );
            }

            $this->entityManager->flush();

            // Mettre à jour les rôles de l'utilisateur
            $this->updateUserRoles($subscription->getUser());

            return $subscription;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation de l\'abonnement', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de synchroniser l\'abonnement: ' . $e->getMessage());
        }
    }

    /**
     * Changer le plan d'un abonnement
     */
    public function changePlan(Subscription $subscription, Plan $newPlan, string $billingInterval): Subscription
    {
        try {
            $priceId = $billingInterval === 'year' ? $newPlan->getStripeYearlyPriceId() : $newPlan->getStripeMonthlyPriceId();
            
            if (!$priceId) {
                throw new \InvalidArgumentException('Prix Stripe non configuré pour ce plan');
            }

            // Mettre à jour l'abonnement sur Stripe
            $stripeSubscription = $this->stripeService->getStripeClient()->subscriptions->update(
                $subscription->getStripeSubscriptionId(),
                [
                    'items' => [
                        [
                            'id' => $subscription->getStripeSubscriptionId(),
                            'price' => $priceId,
                        ],
                    ],
                    'proration_behavior' => 'create_prorations',
                ]
            );

            // Mettre à jour l'entité locale
            $subscription->setPlan($newPlan);
            $subscription->setBillingInterval($billingInterval);
            $subscription->setAmount($stripeSubscription->items->data[0]->price->unit_amount / 100);
            
            $this->entityManager->flush();

            // Mettre à jour les rôles de l'utilisateur
            $this->updateUserRoles($subscription->getUser());

            $this->logger->info('Plan d\'abonnement modifié', [
                'subscription_id' => $subscription->getId(),
                'old_plan' => $subscription->getPlan()?->getSlug(),
                'new_plan' => $newPlan->getSlug(),
                'billing_interval' => $billingInterval
            ]);

            return $subscription;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du changement de plan', [
                'subscription_id' => $subscription->getId(),
                'new_plan_id' => $newPlan->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de changer le plan: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si un utilisateur peut créer plus de produits
     */
    public function canCreateProduct(User $user): bool
    {
        return $user->canCreateProducts();
    }

    /**
     * Vérifier si un utilisateur a accès aux rapports détaillés
     */
    public function hasDetailedReports(User $user): bool
    {
        return $user->hasDetailedReports();
    }

    /**
     * Obtenir les limites du plan actuel d'un utilisateur
     */
    public function getPlanLimits(User $user): array
    {
        $plan = $user->getCurrentPlan();
        
        if (!$plan) {
            return [
                'max_products' => 0,
                'has_detailed_reports' => false,
                'current_products' => $user->getProducts()->count(),
                'can_create_products' => false
            ];
        }

        return [
            'max_products' => $plan->getMaxProducts(),
            'has_detailed_reports' => $plan->hasDetailedReports(),
            'current_products' => $user->getProducts()->count(),
            'can_create_products' => $user->canCreateProducts(),
            'is_unlimited' => $plan->isUnlimited()
        ];
    }

    /**
     * Mettre à jour les rôles d'un utilisateur selon son abonnement
     */
    public function updateUserRoles(User $user): void
    {
        $baseRoles = ['ROLE_USER'];
        $subscriptionRoles = $user->getRolesBySubscription();
        
        $newRoles = array_unique(array_merge($baseRoles, $subscriptionRoles));
        
        $user->setRoles($newRoles);
        $this->entityManager->flush();

        $this->logger->info('Rôles utilisateur mis à jour', [
            'user_id' => $user->getId(),
            'roles' => $newRoles
        ]);
    }

    /**
     * Créer un abonnement gratuit pour un nouvel utilisateur
     */
    public function createFreeSubscription(User $user): ?Subscription
    {
        try {
            $freePlan = $this->planRepository->findFreePlan();
            
            if (!$freePlan) {
                $this->logger->warning('Aucun plan gratuit trouvé');
                return null;
            }

            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setPlan($freePlan);
            $subscription->setStatus(Subscription::STATUS_ACTIVE);
            $subscription->setBillingInterval(Subscription::BILLING_INTERVAL_MONTH);
            $subscription->setAmount(0);
            $subscription->setCurrency('EUR');
            
            // Définir les dates (illimitées pour le plan gratuit)
            $now = new \DateTimeImmutable();
            $subscription->setCurrentPeriodStart($now);
            $subscription->setCurrentPeriodEnd($now->modify('+100 years')); // Effectivement illimité

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            // Mettre à jour les rôles
            $this->updateUserRoles($user);

            $this->logger->info('Abonnement gratuit créé', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId()
            ]);

            return $subscription;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'abonnement gratuit', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtenir les statistiques d'abonnement pour l'admin
     */
    public function getSubscriptionStats(): array
    {
        $activeCount = $this->subscriptionRepository->countActiveSubscriptions();
        $monthlyRevenue = $this->subscriptionRepository->getMonthlyRevenue();
        
        return [
            'active_subscriptions' => $activeCount,
            'monthly_revenue' => $monthlyRevenue,
            'total_users' => $this->entityManager->getRepository(User::class)->count([]),
        ];
    }
}