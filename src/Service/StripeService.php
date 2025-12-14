<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\Invoice;
use App\Entity\PaymentMethod;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentMethodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private StripeClient $stripe;

    public function getStripeClient(): StripeClient
    {
        return $this->stripe;
    }

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository,
        private SubscriptionRepository $subscriptionRepository,
        private InvoiceRepository $invoiceRepository,
        private PaymentMethodRepository $paymentMethodRepository,
        private LoggerInterface $logger,
        private string $stripeSecretKey,
        private string $stripeWebhookSecret
    ) {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    /**
     * Créer ou récupérer un customer Stripe pour un utilisateur
     */
    public function getOrCreateCustomer(User $user): \Stripe\Customer
    {
        try {
            // Si l'utilisateur a déjà un customer ID Stripe
            if ($user->getStripeCustomerId()) {
                try {
                    return $this->stripe->customers->retrieve($user->getStripeCustomerId());
                } catch (ApiErrorException $e) {
                    // Si le customer n'existe plus, on en crée un nouveau
                    $this->logger->warning('Customer Stripe introuvable', [
                        'user_id' => $user->getId(),
                        'stripe_customer_id' => $user->getStripeCustomerId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Créer un nouveau customer
            $customer = $this->stripe->customers->create([
                'email' => $user->getEmail(),
                'metadata' => [
                    'user_id' => $user->getId(),
                ],
            ]);

            // Sauvegarder l'ID customer
            $user->setStripeCustomerId($customer->id);
            $this->entityManager->flush();

            $this->logger->info('Customer Stripe créé', [
                'user_id' => $user->getId(),
                'stripe_customer_id' => $customer->id
            ]);

            return $customer;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la création du customer Stripe', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de créer le customer Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Créer une session de checkout pour un abonnement
     */
    public function createCheckoutSession(User $user, Plan $plan, string $billingInterval, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        try {
            $customer = $this->getOrCreateCustomer($user);
            
            $priceId = $billingInterval === 'year' ? $plan->getStripeYearlyPriceId() : $plan->getStripeMonthlyPriceId();
            
            if (!$priceId) {
                throw new \InvalidArgumentException('Prix Stripe non configuré pour ce plan');
            }

            $sessionData = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $priceId,
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->getId(),
                    'plan_id' => $plan->getId(),
                    'billing_interval' => $billingInterval,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->getId(),
                        'plan_id' => $plan->getId(),
                    ],
                ],
            ];

            // Pas de période d'essai

            $session = $this->stripe->checkout->sessions->create($sessionData);

            $this->logger->info('Session de checkout créée', [
                'user_id' => $user->getId(),
                'plan_id' => $plan->getId(),
                'session_id' => $session->id,
                'billing_interval' => $billingInterval
            ]);

            return $session;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la création de la session de checkout', [
                'user_id' => $user->getId(),
                'plan_id' => $plan->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de créer la session de paiement: ' . $e->getMessage());
        }
    }

    /**
     * Annuler un abonnement
     */
    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true): \Stripe\Subscription
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->getStripeSubscriptionId(),
                [
                    'cancel_at_period_end' => $atPeriodEnd,
                    'metadata' => [
                        'canceled_by_user' => 'true',
                        'canceled_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                ]
            );

            // Mettre à jour l'entité locale
            if (!$atPeriodEnd) {
                $subscription->setStatus(Subscription::STATUS_CANCELED);
                $subscription->setCanceledAt(new \DateTimeImmutable());
                $subscription->setEndsAt(\DateTimeImmutable::createFromFormat('U', $stripeSubscription->canceled_at));
            } else {
                $subscription->setEndsAt(\DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end));
            }

            $this->entityManager->flush();

            $this->logger->info('Abonnement annulé', [
                'subscription_id' => $subscription->getId(),
                'stripe_subscription_id' => $subscription->getStripeSubscriptionId(),
                'at_period_end' => $atPeriodEnd
            ]);

            return $stripeSubscription;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de l\'annulation de l\'abonnement', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible d\'annuler l\'abonnement: ' . $e->getMessage());
        }
    }

    /**
     * Créer un Payment Method à partir d'un token Stripe
     */
    public function createPaymentMethod(User $user, string $paymentMethodId): PaymentMethod
    {
        try {
            $customer = $this->getOrCreateCustomer($user);
            
            // Attacher le payment method au customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customer->id,
            ]);

            // Récupérer les détails du payment method
            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);

            // Créer l'entité locale
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setUser($user);
            $paymentMethod->setStripePaymentMethodId($paymentMethodId);
            $paymentMethod->setType($stripePaymentMethod->type);

            // Traiter les données selon le type
            if ($stripePaymentMethod->type === 'card') {
                $card = $stripePaymentMethod->card;
                $paymentMethod->setCardLast4($card->last4);
                $paymentMethod->setCardBrand($card->brand);
                $paymentMethod->setCardExpMonth($card->exp_month);
                $paymentMethod->setCardExpYear($card->exp_year);
            }

            $paymentMethod->setStripeData($stripePaymentMethod->toArray());

            // Si c'est le premier payment method, le définir comme défaut
            $existingPaymentMethods = $this->paymentMethodRepository->findByUser($user);
            if (empty($existingPaymentMethods)) {
                $paymentMethod->setIsDefault(true);
            }

            $this->entityManager->persist($paymentMethod);
            $this->entityManager->flush();

            $this->logger->info('Payment method créé', [
                'user_id' => $user->getId(),
                'payment_method_id' => $paymentMethod->getId(),
                'stripe_payment_method_id' => $paymentMethodId
            ]);

            return $paymentMethod;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la création du payment method', [
                'user_id' => $user->getId(),
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible d\'ajouter le moyen de paiement: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un Payment Method
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): void
    {
        try {
            // Détacher de Stripe
            $this->stripe->paymentMethods->detach($paymentMethod->getStripePaymentMethodId());

            // Supprimer de la base de données
            $this->entityManager->remove($paymentMethod);
            $this->entityManager->flush();

            $this->logger->info('Payment method supprimé', [
                'payment_method_id' => $paymentMethod->getId(),
                'stripe_payment_method_id' => $paymentMethod->getStripePaymentMethodId()
            ]);

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la suppression du payment method', [
                'payment_method_id' => $paymentMethod->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de supprimer le moyen de paiement: ' . $e->getMessage());
        }
    }

    /**
     * Synchroniser une facture depuis Stripe
     */
    public function syncInvoiceFromStripe(string $stripeInvoiceId): Invoice
    {
        try {
            $stripeInvoice = $this->stripe->invoices->retrieve($stripeInvoiceId);
            
            // Chercher l'utilisateur par customer ID
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'stripeCustomerId' => $stripeInvoice->customer
            ]);

            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé pour la facture');
            }

            // Chercher si la facture existe déjà
            $invoice = $this->invoiceRepository->findByStripeId($stripeInvoiceId);
            
            if (!$invoice) {
                $invoice = new Invoice();
                $invoice->setUser($user);
                $invoice->setStripeInvoiceId($stripeInvoiceId);
                
                // Trouver l'abonnement associé si applicable
                if ($stripeInvoice->subscription) {
                    $subscription = $this->subscriptionRepository->findByStripeId($stripeInvoice->subscription);
                    if ($subscription) {
                        $invoice->setSubscription($subscription);
                    }
                }
            }

            // Mettre à jour les données
            $invoice->setInvoiceNumber($stripeInvoice->number);
            $invoice->setStatus($stripeInvoice->status);
            $invoice->setSubtotal($stripeInvoice->subtotal / 100); // Convertir centimes en euros
            $invoice->setTotal($stripeInvoice->total / 100);
            $invoice->setAmountPaid($stripeInvoice->amount_paid / 100);
            $invoice->setAmountDue($stripeInvoice->amount_due / 100);
            $invoice->setCurrency(strtoupper($stripeInvoice->currency));
            $invoice->setHostedInvoiceUrl($stripeInvoice->hosted_invoice_url);
            $invoice->setInvoicePdf($stripeInvoice->invoice_pdf);
            $invoice->setStripeData($stripeInvoice->toArray());

            if ($stripeInvoice->due_date) {
                $invoice->setDueDate(\DateTimeImmutable::createFromFormat('U', $stripeInvoice->due_date));
            }

            if ($stripeInvoice->status_transitions && $stripeInvoice->status_transitions->paid_at) {
                $invoice->setPaidAt(\DateTimeImmutable::createFromFormat('U', $stripeInvoice->status_transitions->paid_at));
            }

            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            return $invoice;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la synchronisation de la facture', [
                'stripe_invoice_id' => $stripeInvoiceId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de synchroniser la facture: ' . $e->getMessage());
        }
    }

    /**
     * Valider la signature d'un webhook Stripe
     */
    public function validateWebhookSignature(string $payload, string $signature): \Stripe\Event
    {
        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->stripeWebhookSecret
            );
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Signature webhook invalide', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Signature webhook invalide');
        }
    }

    /**
     * Obtenir le portal client Stripe pour la gestion des abonnements
     */
    public function createCustomerPortalSession(User $user, string $returnUrl): \Stripe\BillingPortal\Session
    {
        try {
            $customer = $this->getOrCreateCustomer($user);

            $session = $this->stripe->billingPortal->sessions->create([
                'customer' => $customer->id,
                'return_url' => $returnUrl,
            ]);

            $this->logger->info('Session portal client créée', [
                'user_id' => $user->getId(),
                'session_id' => $session->id
            ]);

            return $session;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur lors de la création de la session portal', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Impossible de créer la session portal: ' . $e->getMessage());
        }
    }
}