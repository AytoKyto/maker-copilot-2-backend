<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\User;
use App\Service\StripeService;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Stripe Event Handler Service
 *
 * Processes Stripe webhook events and delegates to appropriate handlers.
 * Handles checkout completion, subscription updates, and invoice payments.
 *
 * @package App\Service\Stripe
 */
class StripeEventHandler
{
    private StripeService $stripeService;
    private SubscriptionManager $subscriptionManager;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param StripeService $stripeService Stripe API service
     * @param SubscriptionManager $subscriptionManager Subscription management service
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param LoggerInterface $logger Logger service
     */
    public function __construct(
        StripeService $stripeService,
        SubscriptionManager $subscriptionManager,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->stripeService = $stripeService;
        $this->subscriptionManager = $subscriptionManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Handle checkout session completed event
     *
     * Creates subscription and updates user role after successful payment.
     *
     * @param array $session Stripe checkout session data
     *
     * @return void
     *
     * @throws \RuntimeException When user not found or subscription creation fails
     */
    public function handleCheckoutCompleted(array $session): void
    {
        // TODO: Extract logic from StripeWebhookController lines 77-130
        $this->logger->info('Handling checkout completed', ['session_id' => $session['id'] ?? 'unknown']);
    }

    /**
     * Handle subscription updated event
     *
     * Updates local subscription data when Stripe subscription changes.
     *
     * @param array $subscription Stripe subscription data
     *
     * @return void
     */
    public function handleSubscriptionUpdated(array $subscription): void
    {
        // TODO: Extract logic from StripeWebhookController lines 132-180
        $this->logger->info('Handling subscription updated', ['subscription_id' => $subscription['id'] ?? 'unknown']);
    }

    /**
     * Handle invoice paid event
     *
     * Records payment and extends subscription period.
     *
     * @param array $invoice Stripe invoice data
     *
     * @return void
     */
    public function handleInvoicePaid(array $invoice): void
    {
        // TODO: Extract logic from StripeWebhookController lines 182-259
        $this->logger->info('Handling invoice paid', ['invoice_id' => $invoice['id'] ?? 'unknown']);
    }

    /**
     * Handle subscription deleted event
     *
     * Downgrades user when subscription is cancelled.
     *
     * @param array $subscription Stripe subscription data
     *
     * @return void
     */
    public function handleSubscriptionDeleted(array $subscription): void
    {
        $this->logger->info('Handling subscription deleted', ['subscription_id' => $subscription['id'] ?? 'unknown']);
    }
}
