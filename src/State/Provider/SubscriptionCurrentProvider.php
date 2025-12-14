<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Service\SubscriptionManager;
use Symfony\Component\Security\Core\Security;

/**
 * @implements ProviderInterface<object>
 */
final class SubscriptionCurrentProvider implements ProviderInterface
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return null;
        }
        
        $currentSubscription = $user->getActiveSubscription();
        $planLimits = $this->subscriptionManager->getPlanLimits($user);

        return [
            'subscription' => $currentSubscription,
            'plan_limits' => $planLimits,
            'user_stats' => [
                'products_count' => $user->getProducts()->count(),
                'sales_count' => $user->getSales()->count()
            ]
        ];
    }
}