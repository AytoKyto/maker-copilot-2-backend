<?php

declare(strict_types=1);

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Authentication Failure Listener
 *
 * Handles authentication failure events.
 * Returns standardized error response.
 *
 * @package App\EventListener
 */
class AuthenticationFailureListener
{
    /**
     * Handle authentication failure event
     *
     * @param AuthenticationFailureEvent $event Authentication failure event
     *
     * @return void
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $response = new JsonResponse([
            'error' => 'authentication_failed',
            'message' => 'Invalid credentials'
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
