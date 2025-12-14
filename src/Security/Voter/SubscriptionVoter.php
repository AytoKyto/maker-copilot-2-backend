<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\SubscriptionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SubscriptionVoter extends Voter
{
    public const CREATE_PRODUCT = 'CREATE_PRODUCT';
    public const ACCESS_DETAILED_REPORTS = 'ACCESS_DETAILED_REPORTS';
    public const ACCESS_UNLIMITED_FEATURES = 'ACCESS_UNLIMITED_FEATURES';
    public const MANAGE_TEAM = 'MANAGE_TEAM';

    public function __construct(
        private SubscriptionManager $subscriptionManager
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::CREATE_PRODUCT,
            self::ACCESS_DETAILED_REPORTS,
            self::ACCESS_UNLIMITED_FEATURES,
            self::MANAGE_TEAM,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE_PRODUCT => $this->canCreateProduct($user),
            self::ACCESS_DETAILED_REPORTS => $this->canAccessDetailedReports($user),
            self::ACCESS_UNLIMITED_FEATURES => $this->canAccessUnlimitedFeatures($user),
            self::MANAGE_TEAM => $this->canManageTeam($user),
            default => false,
        };
    }

    private function canCreateProduct(User $user): bool
    {
        return $this->subscriptionManager->canCreateProduct($user);
    }

    private function canAccessDetailedReports(User $user): bool
    {
        return $this->subscriptionManager->hasDetailedReports($user);
    }

    private function canAccessUnlimitedFeatures(User $user): bool
    {
        $plan = $user->getCurrentPlan();
        
        if (!$plan) {
            return false;
        }

        return $plan->isUnlimited();
    }

    private function canManageTeam(User $user): bool
    {
        $plan = $user->getCurrentPlan();
        
        if (!$plan) {
            return false;
        }

        // Seuls les plans Pro et Unlimited permettent la gestion d'équipe
        return in_array($plan->getSlug(), ['pro', 'unlimited']);
    }
}