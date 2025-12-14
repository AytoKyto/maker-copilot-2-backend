<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Plan;
use App\Repository\UserRepository;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private SubscriptionManager $subscriptionManager,
        private UserRepository $userRepository,
        private PlanRepository $planRepository,
        private SubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$signature) {
            $this->logger->error('Webhook Stripe reçu sans signature');
            return new Response('No signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Valider la signature du webhook
            $event = $this->stripeService->validateWebhookSignature($payload, $signature);
            
            $this->logger->info('Webhook Stripe reçu', [
                'event_type' => $event->type,
                'event_id' => $event->id
            ]);

            // Traiter l'événement selon son type
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'invoice.created' => $this->handleInvoiceCreated($event),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($event),
                default => $this->logger->info('Événement webhook non traité', ['type' => $event->type])
            };

            return new Response('OK', Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement du webhook Stripe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new Response('Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleCheckoutSessionCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;

        $this->logger->info('Checkout session complétée', [
            'session_id' => $session->id,
            'customer_id' => $session->customer,
            'subscription_id' => $session->subscription
        ]);

        if ($session->mode === 'subscription' && $session->subscription) {
            // Récupérer l'utilisateur via les métadonnées
            $userId = $session->metadata->user_id ?? null;
            $planId = $session->metadata->plan_id ?? null;

            if (!$userId || !$planId) {
                $this->logger->error('Métadonnées manquantes dans la session de checkout', [
                    'session_id' => $session->id
                ]);
                return;
            }

            $user = $this->userRepository->find($userId);
            $plan = $this->planRepository->find($planId);

            if (!$user || !$plan) {
                $this->logger->error('Utilisateur ou plan non trouvé', [
                    'user_id' => $userId,
                    'plan_id' => $planId
                ]);
                return;
            }

            // Créer l'abonnement
            $this->subscriptionManager->createSubscriptionFromStripe(
                $session->subscription,
                $user,
                $plan
            );
        }
    }

    private function handleSubscriptionCreated(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        
        $this->logger->info('Abonnement créé sur Stripe', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer,
            'status' => $subscription->status
        ]);

        // L'abonnement est généralement déjà créé via checkout.session.completed
        // Mais on peut synchroniser pour s'assurer que tout est à jour
        $existingSubscription = $this->subscriptionRepository->findByStripeId($subscription->id);
        if ($existingSubscription) {
            $this->subscriptionManager->syncSubscriptionFromStripe($subscription->id);
        }
    }

    private function handleSubscriptionUpdated(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        
        $this->logger->info('Abonnement mis à jour sur Stripe', [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);

        // Synchroniser l'abonnement
        $existingSubscription = $this->subscriptionRepository->findByStripeId($subscription->id);
        if ($existingSubscription) {
            $this->subscriptionManager->syncSubscriptionFromStripe($subscription->id);
        }
    }

    private function handleSubscriptionDeleted(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        
        $this->logger->info('Abonnement supprimé sur Stripe', [
            'subscription_id' => $subscription->id
        ]);

        // Marquer l'abonnement comme annulé
        $existingSubscription = $this->subscriptionRepository->findByStripeId($subscription->id);
        if ($existingSubscription) {
            $existingSubscription->setStatus(\App\Entity\Subscription::STATUS_CANCELED);
            $existingSubscription->setCanceledAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            // Mettre à jour les rôles de l'utilisateur
            $this->subscriptionManager->updateUserRoles($existingSubscription->getUser());
        }
    }

    private function handleInvoicePaymentSucceeded(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        
        $this->logger->info('Paiement de facture réussi', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription,
            'amount_paid' => $invoice->amount_paid / 100
        ]);

        // Synchroniser la facture
        $this->stripeService->syncInvoiceFromStripe($invoice->id);

        // Si c'est le premier paiement d'un abonnement, s'assurer que le statut est actif
        if ($invoice->subscription) {
            $subscription = $this->subscriptionRepository->findByStripeId($invoice->subscription);
            if ($subscription && $subscription->getStatus() !== \App\Entity\Subscription::STATUS_ACTIVE) {
                $subscription->setStatus(\App\Entity\Subscription::STATUS_ACTIVE);
                $this->entityManager->flush();
                
                $this->subscriptionManager->updateUserRoles($subscription->getUser());
            }
        }
    }

    private function handleInvoicePaymentFailed(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        
        $this->logger->warning('Échec du paiement de facture', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription,
            'amount_due' => $invoice->amount_due / 100
        ]);

        // Synchroniser la facture
        $this->stripeService->syncInvoiceFromStripe($invoice->id);

        // Marquer l'abonnement comme en retard de paiement si applicable
        if ($invoice->subscription) {
            $subscription = $this->subscriptionRepository->findByStripeId($invoice->subscription);
            if ($subscription) {
                $subscription->setStatus(\App\Entity\Subscription::STATUS_PAST_DUE);
                $this->entityManager->flush();
                
                $this->subscriptionManager->updateUserRoles($subscription->getUser());
            }
        }

        // TODO: Envoyer un email de notification à l'utilisateur
    }

    private function handleInvoiceCreated(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        
        $this->logger->info('Facture créée', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);

        // Synchroniser la facture
        $this->stripeService->syncInvoiceFromStripe($invoice->id);
    }

    private function handleTrialWillEnd(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        
        $this->logger->info('Fin de période d\'essai dans 3 jours', [
            'subscription_id' => $subscription->id,
            'trial_end' => $subscription->trial_end
        ]);

        // TODO: Envoyer un email de notification à l'utilisateur
        $existingSubscription = $this->subscriptionRepository->findByStripeId($subscription->id);
        if ($existingSubscription) {
            $user = $existingSubscription->getUser();
            
            $this->logger->info('Notification de fin d\'essai à envoyer', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
            
            // Ici, on pourrait déclencher l'envoi d'un email via un service de mail
        }
    }
}