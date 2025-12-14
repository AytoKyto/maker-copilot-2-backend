<?php

declare(strict_types=1);

namespace App\Service\Subscription;

use App\Entity\User;
use App\Entity\Subscription;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Subscription Initialization Service
 *
 * Handles creation of initial subscriptions for new users.
 * Provides consistent subscription setup across registration flows.
 *
 * @package App\Service\Subscription
 */
class SubscriptionInitializationService
{
    private SubscriptionManager $subscriptionManager;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param SubscriptionManager $subscriptionManager Subscription management service
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param LoggerInterface $logger Logger service
     */
    public function __construct(
        SubscriptionManager $subscriptionManager,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Create starter subscription for new user
     *
     * Creates a free trial or starter plan subscription.
     * Used during user registration.
     *
     * @param User $user User entity
     * @param int $trialDays Number of trial days (default: 14)
     *
     * @return Subscription Created subscription entity
     *
     * @throws \RuntimeException When subscription creation fails
     */
    public function createStarterSubscription(User $user, int $trialDays = 14): Subscription
    {
        // TODO: Extract and consolidate logic from:
        // - SubscriptionController lines 56-81, 349-379
        // - RegistrationController lines 148-172

        $this->logger->info('Creating starter subscription', [
            'user_id' => $user->getId(),
            'trial_days' => $trialDays
        ]);

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setStatus('trial');
        $subscription->setStartDate(new \DateTimeImmutable());
        $subscription->setEndDate(new \DateTimeImmutable("+{$trialDays} days"));

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Upgrade user subscription to paid plan
     *
     * @param User $user User entity
     * @param string $planId Plan identifier
     * @param string $stripeSubscriptionId Stripe subscription ID
     *
     * @return Subscription Updated subscription entity
     */
    public function upgradeSubscription(
        User $user,
        string $planId,
        string $stripeSubscriptionId
    ): Subscription {
        $this->logger->info('Upgrading subscription', [
            'user_id' => $user->getId(),
            'plan_id' => $planId
        ]);

        // TODO: Implement upgrade logic
        $subscription = $user->getSubscription();

        return $subscription;
    }

    /**
     * Check if user has active subscription
     *
     * @param User $user User entity
     *
     * @return bool True if user has active subscription
     */
    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $user->getSubscription();

        if (!$subscription) {
            return false;
        }

        return $subscription->getStatus() === 'active'
            || ($subscription->getStatus() === 'trial' && $subscription->getEndDate() > new \DateTimeImmutable());
    }
}
