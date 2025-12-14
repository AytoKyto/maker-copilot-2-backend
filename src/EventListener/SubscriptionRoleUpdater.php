<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\SubscriptionManager;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use App\Entity\Subscription;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Subscription::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Subscription::class)]
class SubscriptionRoleUpdater
{
    public function __construct(
        private SubscriptionManager $subscriptionManager
    ) {
    }

    public function postPersist(Subscription $subscription, PostPersistEventArgs $event): void
    {
        $this->updateUserRoles($subscription);
    }

    public function postUpdate(Subscription $subscription, PostUpdateEventArgs $event): void
    {
        $this->updateUserRoles($subscription);
    }

    private function updateUserRoles(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        
        if ($user instanceof User) {
            $this->subscriptionManager->updateUserRoles($user);
        }
    }
}