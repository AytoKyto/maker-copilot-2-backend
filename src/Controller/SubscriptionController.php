<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Psr\Log\LoggerInterface;

#[AsController]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private SubscriptionManager $subscriptionManager,
        private PlanRepository $planRepository,
        private SubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $subscriptions = $user->getSubscriptions();
        $currentSubscription = $user->getActiveSubscription();
        $planLimits = $this->subscriptionManager->getPlanLimits($user);

        return $this->json([
            'current_subscription' => $currentSubscription,
            'subscriptions' => $subscriptions,
            'plan_limits' => $planLimits
        ], 200, [], ['groups' => ['subscription:read']]);
    }

    public function current(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $currentSubscription = $user->getActiveSubscription();
        
        // Si l'utilisateur n'a pas de subscription, créer une subscription gratuite
        if (!$currentSubscription) {
            $starterPlan = $this->planRepository->findOneBy(['slug' => 'starter']);
            if ($starterPlan) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setPlan($starterPlan);
                $subscription->setStripeSubscriptionId('sub_free_' . uniqid() . '_' . $user->getId());
                $subscription->setStatus('active');
                $subscription->setBillingInterval('month');
                $subscription->setAmount(0);
                $subscription->setCurrency('EUR');
                $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
                $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('+30 days'));
                $subscription->setCreatedAt(new \DateTimeImmutable());
                $subscription->setUpdatedAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($subscription);
                $this->entityManager->flush();
                
                $currentSubscription = $subscription;
                $this->logger->info('Subscription gratuite créée automatiquement pour l\'utilisateur', [
                    'user_id' => $user->getId()
                ]);
            }
        }
        
        $planLimits = $this->subscriptionManager->getPlanLimits($user);

        return $this->json([
            'subscription' => $currentSubscription,
            'plan_limits' => $planLimits,
            'user_stats' => [
                'products_count' => $user->getProducts()->count(),
                'sales_count' => $user->getSales()->count()
            ]
        ], 200, [], ['groups' => ['subscription:read', 'plan:read']]);
    }

    public function createCheckout(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['plan_slug']) || !isset($data['billing_interval'])) {
                return $this->json([
                    'error' => 'Plan et intervalle de facturation requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $planSlug = $data['plan_slug'];
            $billingInterval = $data['billing_interval'];
            
            if (!in_array($billingInterval, ['month', 'year'])) {
                return $this->json([
                    'error' => 'Intervalle de facturation invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier que le plan existe
            $plan = $this->planRepository->findOneBy(['slug' => $planSlug]);
            if (!$plan) {
                return $this->json([
                    'error' => 'Plan non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Ne pas permettre de souscrire au plan gratuit via checkout
            if ($plan->isFree()) {
                return $this->json([
                    'error' => 'Le plan gratuit ne peut pas être souscrit via checkout'
                ], Response::HTTP_BAD_REQUEST);
            }

            // URLs de redirection
            $successUrl = $data['success_url'] ?? $this->generateUrl('subscription_success', [], true);
            $cancelUrl = $data['cancel_url'] ?? $this->generateUrl('subscription_cancel', [], true);

            // Créer la session de checkout
            $session = $this->stripeService->createCheckoutSession(
                $user,
                $plan,
                $billingInterval,
                $successUrl,
                $cancelUrl
            );

            $this->logger->info('Session de checkout créée', [
                'user_id' => $user->getId(),
                'plan_slug' => $planSlug,
                'billing_interval' => $billingInterval,
                'session_id' => $session->id
            ]);

            return $this->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du checkout', [
                'user_id' => $this->getUser()->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de la création de la session de paiement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancel(Subscription $subscription, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que l'abonnement appartient à l'utilisateur
            if ($subscription->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Vérifier que l'abonnement est actif
            if (!$subscription->isActive()) {
                return $this->json([
                    'error' => 'L\'abonnement n\'est pas actif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = json_decode($request->getContent(), true);
            $atPeriodEnd = $data['at_period_end'] ?? true;

            // Annuler l'abonnement
            $this->subscriptionManager->cancelSubscription($subscription, $atPeriodEnd);

            $this->logger->info('Abonnement annulé par l\'utilisateur', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'at_period_end' => $atPeriodEnd
            ]);

            return $this->json([
                'message' => $atPeriodEnd 
                    ? 'Abonnement annulé à la fin de la période en cours'
                    : 'Abonnement annulé immédiatement',
                'subscription' => $subscription
            ], 200, [], ['groups' => ['subscription:read']]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'annulation de l\'abonnement', [
                'user_id' => $this->getUser()->getId(),
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de l\'annulation de l\'abonnement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changePlan(Subscription $subscription, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que l'abonnement appartient à l'utilisateur
            if ($subscription->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Vérifier que l'abonnement est actif
            if (!$subscription->isActive()) {
                return $this->json([
                    'error' => 'L\'abonnement n\'est pas actif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['plan_slug']) || !isset($data['billing_interval'])) {
                return $this->json([
                    'error' => 'Plan et intervalle de facturation requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $newPlan = $this->planRepository->findBySlug($data['plan_slug']);
            if (!$newPlan) {
                return $this->json([
                    'error' => 'Plan non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier qu'il s'agit d'un changement
            if ($subscription->getPlan()->getId() === $newPlan->getId()) {
                return $this->json([
                    'error' => 'Vous êtes déjà sur ce plan'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Changer le plan
            $updatedSubscription = $this->subscriptionManager->changePlan(
                $subscription,
                $newPlan,
                $data['billing_interval']
            );

            $this->logger->info('Plan d\'abonnement modifié', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'old_plan' => $subscription->getPlan()->getSlug(),
                'new_plan' => $newPlan->getSlug()
            ]);

            return $this->json([
                'message' => 'Plan modifié avec succès',
                'subscription' => $updatedSubscription
            ], 200, [], ['groups' => ['subscription:read']]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du changement de plan', [
                'user_id' => $this->getUser()->getId(),
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors du changement de plan'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function customerPortal(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $data = json_decode($request->getContent(), true);
            $returnUrl = $data['return_url'] ?? $this->generateUrl('subscription_current', [], true);

            // Vérifier si l'utilisateur a un abonnement payant
            $currentSubscription = $user->getActiveSubscription();
            if (!$currentSubscription || $currentSubscription->getPlan()->isFree()) {
                return $this->json([
                    'portal_url' => null,
                    'message' => 'Le portail client n\'est disponible que pour les abonnements payants. Souscrivez à un plan Pro ou Unlimited pour accéder à cette fonctionnalité.',
                    'requires_paid_plan' => true
                ]);
            }

            // En mode développement sans customer Stripe valide
            if (!$user->getStripeCustomerId() || str_starts_with($user->getStripeCustomerId() ?? '', 'sub_free_')) {
                $this->logger->warning('Customer portal demandé sans customer Stripe valide', [
                    'user_id' => $user->getId(),
                    'stripe_customer_id' => $user->getStripeCustomerId()
                ]);
                
                return $this->json([
                    'portal_url' => null,
                    'message' => 'Le portail client nécessite un abonnement Stripe valide. Veuillez d\'abord souscrire à un plan payant.',
                    'requires_stripe_customer' => true
                ]);
            }

            // Créer une session portal client
            $session = $this->stripeService->createCustomerPortalSession($user, $returnUrl);

            return $this->json([
                'portal_url' => $session->url
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du portal client', [
                'user_id' => $this->getUser()->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de la création du portal client: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkLimits(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur a une subscription
        $currentSubscription = $user->getActiveSubscription();
        if (!$currentSubscription) {
            // Créer une subscription gratuite si elle n'existe pas
            $starterPlan = $this->planRepository->findOneBy(['slug' => 'starter']);
            if ($starterPlan) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setPlan($starterPlan);
                $subscription->setStripeSubscriptionId('sub_free_' . uniqid() . '_' . $user->getId());
                $subscription->setStatus('active');
                $subscription->setBillingInterval('month');
                $subscription->setAmount(0);
                $subscription->setCurrency('EUR');
                $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
                $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('+30 days'));
                $subscription->setCreatedAt(new \DateTimeImmutable());
                $subscription->setUpdatedAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($subscription);
                $this->entityManager->flush();
                
                $this->logger->info('Subscription gratuite créée automatiquement pour l\'utilisateur', [
                    'user_id' => $user->getId()
                ]);
            }
        }
        
        $action = $request->query->get('action');
        $planLimits = $this->subscriptionManager->getPlanLimits($user);

        $response = [
            'plan_limits' => $planLimits,
            'allowed' => true,
            'message' => null
        ];

        switch ($action) {
            case 'create_product':
                $response['allowed'] = $this->subscriptionManager->canCreateProduct($user);
                if (!$response['allowed']) {
                    $planName = $user->getCurrentPlan()?->getName() ?? 'Starter';
                    $response['message'] = "Vous avez atteint la limite de {$planLimits['max_products']} produits pour votre plan {$planName}. Passez à un plan supérieur pour créer plus de produits.";
                    $response['upgrade_required'] = true;
                } else if (!$planLimits['is_unlimited']) {
                    $remaining = $planLimits['max_products'] - $planLimits['current_products'];
                    $response['message'] = "Vous pouvez créer {$remaining} produit(s) supplémentaire(s).";
                }
                break;
                
            case 'detailed_reports':
                $response['allowed'] = $this->subscriptionManager->hasDetailedReports($user);
                if (!$response['allowed']) {
                    $response['message'] = 'Les rapports détaillés ne sont pas disponibles sur votre plan';
                    $response['upgrade_required'] = true;
                }
                break;
        }

        return $this->json($response);
    }

    // Routes publiques pour les redirections après paiement

    public function success(): JsonResponse
    {
        return $this->json([
            'message' => 'Paiement réussi ! Votre abonnement sera activé sous peu.',
            'status' => 'success'
        ]);
    }

    public function cancelPage(): JsonResponse
    {
        return $this->json([
            'message' => 'Paiement annulé. Vous pouvez réessayer à tout moment.',
            'status' => 'canceled'
        ]);
    }
}