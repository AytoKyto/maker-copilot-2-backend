<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Auth Controller
 *
 * Handles authentication-related endpoints.
 * Provides logout and auth check functionality.
 *
 * @package App\Controller
 */
class AuthController extends AbstractController
{
    /**
     * Logout endpoint
     *
     * Client should discard tokens on their side.
     * Optionally can implement token blacklisting here.
     *
     * @return JsonResponse Success response
     */
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // With JWT, logout is handled client-side by discarding tokens
        // Optionally: implement refresh token blacklisting here

        return new JsonResponse([
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Check authentication status
     *
     * Returns current user info if authenticated.
     *
     * @return JsonResponse User info or unauthorized error
     */
    #[Route('/api/auth/check', name: 'api_auth_check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'authenticated' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'authenticated' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ], Response::HTTP_OK);
    }
}
