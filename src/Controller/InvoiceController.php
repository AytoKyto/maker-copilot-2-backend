<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/api/invoices', name: 'invoice_')]
#[IsGranted('ROLE_USER')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private StripeService $stripeService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $status = $request->query->get('status');
        
        $invoices = $this->invoiceRepository->findByUserPaginated(
            $user, 
            $page, 
            $limit, 
            $status
        );
        
        $total = $this->invoiceRepository->countByUser($user, $status);
        
        return $this->json([
            'invoices' => $invoices,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], 200, [], ['groups' => ['invoice:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Invoice $invoice): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la facture appartient à l'utilisateur
        if ($invoice->getUser() !== $user) {
            return $this->json([
                'error' => 'Accès refusé'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json($invoice, 200, [], ['groups' => ['invoice:read', 'invoice:details']]);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    public function download(Invoice $invoice): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que la facture appartient à l'utilisateur
            if ($invoice->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Retourner les URLs de téléchargement
            return $this->json([
                'pdf_url' => $invoice->getInvoicePdf(),
                'hosted_url' => $invoice->getHostedInvoiceUrl(),
                'invoice_number' => $invoice->getInvoiceNumber()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement de la facture', [
                'user_id' => $this->getUser()->getId(),
                'invoice_id' => $invoice->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors du téléchargement de la facture'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/sync', name: 'sync', methods: ['POST'])]
    public function sync(Invoice $invoice): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier que la facture appartient à l'utilisateur
            if ($invoice->getUser() !== $user) {
                return $this->json([
                    'error' => 'Accès refusé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Synchroniser avec Stripe
            $updatedInvoice = $this->stripeService->syncInvoiceFromStripe(
                $invoice->getStripeInvoiceId()
            );

            $this->logger->info('Facture synchronisée avec Stripe', [
                'user_id' => $user->getId(),
                'invoice_id' => $invoice->getId(),
                'stripe_invoice_id' => $invoice->getStripeInvoiceId()
            ]);

            return $this->json([
                'message' => 'Facture synchronisée avec succès',
                'invoice' => $updatedInvoice
            ], 200, [], ['groups' => ['invoice:read']]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation de la facture', [
                'user_id' => $this->getUser()->getId(),
                'invoice_id' => $invoice->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Erreur lors de la synchronisation de la facture'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $stats = $this->invoiceRepository->getUserInvoiceStats($user);
        
        return $this->json($stats);
    }
}