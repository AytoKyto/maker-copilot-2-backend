<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\RecaptchaValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class LoginListener implements EventSubscriberInterface
{
    private RecaptchaValidator $recaptchaValidator;
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(
        RecaptchaValidator $recaptchaValidator,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->recaptchaValidator = $recaptchaValidator;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', 256], // Priorité plus basse
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request || $request->getPathInfo() !== '/api/login') {
            return;
        }

        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            throw new \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException(
                'Données de connexion invalides'
            );
        }

        // Vérifier la présence du token reCAPTCHA (tolérant)
        if (!isset($data['recaptcha_token']) || empty($data['recaptcha_token'])) {
            $this->logger->warning('Login attempt without reCAPTCHA token', [
                'ip' => $request->getClientIp() ?? '127.0.0.1'
            ]);
            // Ne pas bloquer pour le moment - juste logger
            return;
        }

        // Valider le token reCAPTCHA
        $clientIp = $request->getClientIp() ?? '127.0.0.1';
        try {
            $recaptchaResult = $this->recaptchaValidator->validateLogin($data['recaptcha_token'], $clientIp);

            if (!$recaptchaResult['success']) {
                $this->logger->warning('reCAPTCHA validation failed during login', [
                    'ip' => $clientIp,
                    'errors' => $recaptchaResult['errors'],
                    'score' => $recaptchaResult['score']
                ]);
                
                // Temporairement tolérant
                return;
            }

            // Vérifier le score reCAPTCHA
            if ($recaptchaResult['score'] < 0.3) { // Score plus permissif
                $this->logger->warning('reCAPTCHA score too low during login', [
                    'ip' => $clientIp,
                    'score' => $recaptchaResult['score']
                ]);
                
                // Pour le moment, ne pas bloquer
                return;
            }
            
            $this->logger->info('reCAPTCHA validation successful', [
                'ip' => $clientIp,
                'score' => $recaptchaResult['score']
            ]);
        } catch (\Exception $e) {
            $this->logger->error('reCAPTCHA validation error', [
                'ip' => $clientIp,
                'error' => $e->getMessage()
            ]);
            // Ne pas bloquer en cas d'erreur technique
            return;
        }
    }
}