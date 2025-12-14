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
final class SubscriptionLimitsProvider implements ProviderInterface
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
        
        $planLimits = $this->subscriptionManager->getPlanLimits($user);
        $currentUsage = [
            'products_count' => $user->getProducts()->count(),
            'sales_count' => $user->getSales()->count()
        ];

        return [
            'plan_limits' => $planLimits,
            'current_usage' => $currentUsage,
            'limits_reached' => [
                'products' => $planLimits['max_products'] > 0 && $currentUsage['products_count'] >= $planLimits['max_products'],
                'sales' => isset($planLimits['max_sales']) && $planLimits['max_sales'] > 0 && $currentUsage['sales_count'] >= $planLimits['max_sales']
            ]
        ];
    }
}