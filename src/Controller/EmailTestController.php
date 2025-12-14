<?php

declare(strict_types=1);
// src/Controller/EmailTestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailTestController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/test-email', name: 'test_email', methods: ['POST'])]
    public function testEmail(Request $request, MailerInterface $mailer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Email de test par défaut ou celui fourni
            $testEmail = $data['email'] ?? 'test@example.com';
            
            // Validation de l'email
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Email invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Rendu du template
            $htmlContent = $this->renderView('email/welcome.html.twig', [
                'email' => $testEmail
            ]);

            // Création et envoi de l'email
            $email = (new Email())
                ->from('no-reply@maker-copilot.com')
                ->to($testEmail)
                ->subject('Test - Bienvenue sur Maker Copilot!')
                ->html($htmlContent);

            $mailer->send($email);
            
            $this->logger->info('Email de test envoyé avec succès à ' . $testEmail);

            return new JsonResponse([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à ' . $testEmail
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de test: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(),
                'details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/email-config', name: 'email_config', methods: ['GET'])]
    public function getEmailConfig(): JsonResponse
    {
        try {
            // Récupération de la configuration email depuis les variables d'environnement
            $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Configuration non trouvée';
            $fromEmail = $_ENV['MAILER_FROM'] ?? 'no-reply@maker-copilot.com';
            
            // Masquer les informations sensibles
            $dsnParts = parse_url($mailerDsn);
            $safeConfig = [
                'scheme' => $dsnParts['scheme'] ?? 'unknown',
                'host' => $dsnParts['host'] ?? 'unknown',
                'port' => $dsnParts['port'] ?? 'default',
                'from_email' => $fromEmail,
                'configured' => $mailerDsn !== 'Configuration non trouvée'
            ];

            return new JsonResponse([
                'success' => true,
                'config' => $safeConfig,
                'message' => $safeConfig['configured'] 
                    ? 'Configuration email trouvée' 
                    : 'Aucune configuration email trouvée dans .env'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la configuration'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}