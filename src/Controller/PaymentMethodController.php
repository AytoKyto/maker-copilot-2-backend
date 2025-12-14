<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\PaymentMethod;
use App\Repository\PaymentMethodRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/api/payment-methods', name: 'payment_method_')]
#[IsGranted('ROLE_USER')]
class PaymentMethodController extends AbstractController
{
    public function __construct(
        private PaymentMethodRepository $paymentMethodRepository,
        private StripeService $stripeService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $paymentMethods = $this->paymentMethodRepository->findByUser($user);
        
        return $this->json($paymentMethods, 200, [], ['groups' => ['payment_method:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['payment_method_id'])) {
                return $this->json([
                    'error' => 'ID du moyen de paiement requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $paymentMethodId = $data['payment_method_id'];
            
            // Vérifier que le payment method n'existe pas déjà
            $existingPaymentMethod = $this->paymentMethodRepository->findByStripeId($paymentMethodId);
            if ($existingPaymentMethod) {
                return $this->json([
                    'error' => 'Ce moyen de paiement existe déjà'
                ], Response::HTTP_CONFLICT);
            }

            // Créer le payment method
            $paymentMethod = $this->stripeService->createPaymentMethod($user, $paymentMethodId);

            $this->logger->info('Moyen de paiement ajouté', [
                'user_id' => $user->getId(),
                'payment_method_id' => $paymentMethod->getId()
            ]);

            return $this->json([
                'message' => 'Moyen de paiement ajouté avec succès',
                'payment_method' => $paymentMethod
            ], Response::HTTP_CREATED, [], ['groups' => ['payment_method:read']]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout du moyen de paiement', [
                'user_id' => $this->getUser()->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de l\'ajout du moyen de paiement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que le payment method appartient à l'utilisateur
        if ($paymentMethod->getUser() !== $user) {
            return $this->json([
                'error' => 'Accès refusé'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json($paymentMethod, 200, [], ['groups' => ['payment_method:read', 'payment_method:details']]);
    }

    #[Route('/{id}/set-default', name: 'set_default', methods: ['POST'])]
    public function setDefault(PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que le payment method appartient à l'utilisateur
            if ($paymentMethod->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Retirer le défaut de tous les autres payment methods
            $userPaymentMethods = $this->paymentMethodRepository->findByUser($user);
            foreach ($userPaymentMethods as $pm) {
                $pm->setIsDefault(false);
            }

            // Définir celui-ci comme défaut
            $paymentMethod->setIsDefault(true);
            
            $this->entityManager->flush();

            $this->logger->info('Moyen de paiement défini comme défaut', [
                'user_id' => $user->getId(),
                'payment_method_id' => $paymentMethod->getId()
            ]);

            return $this->json([
                'message' => 'Moyen de paiement défini comme défaut',
                'payment_method' => $paymentMethod
            ], 200, [], ['groups' => ['payment_method:read']]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la définition du moyen de paiement par défaut', [
                'user_id' => $this->getUser()->getId(),
                'payment_method_id' => $paymentMethod->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de la définition du moyen de paiement par défaut'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que le payment method appartient à l'utilisateur
            if ($paymentMethod->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Vérifier qu'il ne s'agit pas du seul moyen de paiement avec abonnement actif
            $activeSubscription = $user->getActiveSubscription();
            if ($activeSubscription && $paymentMethod->isDefault()) {
                $userPaymentMethods = $this->paymentMethodRepository->findByUser($user);
                if (count($userPaymentMethods) <= 1) {
                    return $this->json([
                        'error' => 'Impossible de supprimer le seul moyen de paiement avec un abonnement actif'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Supprimer le payment method
            $this->stripeService->deletePaymentMethod($paymentMethod);

            $this->logger->info('Moyen de paiement supprimé', [
                'user_id' => $user->getId(),
                'payment_method_id' => $paymentMethod->getId()
            ]);

            return $this->json([
                'message' => 'Moyen de paiement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du moyen de paiement', [
                'user_id' => $this->getUser()->getId(),
                'payment_method_id' => $paymentMethod->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de la suppression du moyen de paiement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}