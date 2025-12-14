<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use App\Entity\User;
use App\Service\SubscriptionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ProductCreationProcessor implements ProcessorInterface
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Vérification uniquement pour les nouvelles créations (POST) de produits
        if ($data instanceof Product && $operation instanceof \ApiPlatform\Metadata\Post && !$data->getId()) {
            /** @var User $user */
            $user = $this->security->getUser();
            
            if ($user && !$this->subscriptionManager->canCreateProduct($user)) {
                $planLimits = $this->subscriptionManager->getPlanLimits($user);
                $planName = $user->getCurrentPlan()?->getName() ?? 'Starter';
                
                throw new AccessDeniedHttpException(
                    "Vous avez atteint la limite de {$planLimits['max_products']} produits pour votre plan {$planName}. Passez à un plan supérieur pour créer plus de produits."
                );
            }
        }

        // Retourner les données sans modification - API Platform se charge de la persistance
        return $data;
    }
}