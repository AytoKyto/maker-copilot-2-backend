<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Service\SubscriptionManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SubscriptionAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private LoggerInterface $logger
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Cet authenticator est utilisé pour vérifier les limites d'abonnement
        // Il ne fournit pas d'authentification mais valide les permissions
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        throw new \LogicException('This authenticator should not be used for authentication');
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessageKey()
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Vérifier si un utilisateur peut effectuer une action spécifique
     */
    public function checkSubscriptionLimits(User $user, string $action): bool
    {
        return match ($action) {
            'create_product' => $this->subscriptionManager->canCreateProduct($user),
            'detailed_reports' => $this->subscriptionManager->hasDetailedReports($user),
            'unlimited_access' => $this->hasUnlimitedAccess($user),
            default => false,
        };
    }

    private function hasUnlimitedAccess(User $user): bool
    {
        $plan = $user->getCurrentPlan();
        return $plan && $plan->isUnlimited();
    }
}