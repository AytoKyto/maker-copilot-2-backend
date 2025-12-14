<?php

declare(strict_types=1);

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authentication Success Listener
 *
 * Handles successful authentication by formatting the JWT response.
 * Returns access_token and refresh_token in JSON body.
 *
 * @package App\EventListener
 */
class AuthenticationSuccessListener
{
    private RefreshTokenGeneratorInterface $refreshTokenGenerator;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private int $tokenTtl;

    /**
     * Constructor
     *
     * @param RefreshTokenGeneratorInterface $refreshTokenGenerator Refresh token generator
     * @param RefreshTokenManagerInterface $refreshTokenManager Refresh token manager
     * @param int $tokenTtl JWT token TTL in seconds
     */
    public function __construct(
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        int $tokenTtl = 900
    ) {
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->tokenTtl = $tokenTtl;
    }

    /**
     * Handle authentication success event
     *
     * Formats response with access_token, refresh_token, token_type and expires_in.
     *
     * @param AuthenticationSuccessEvent $event Authentication success event
     *
     * @return void
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        // Format response with standard OAuth2-like structure
        $responseData = [
            'access_token' => $data['token'] ?? null,
            'token_type' => 'Bearer',
            'expires_in' => $this->tokenTtl,
        ];

        // Generate refresh token if user is valid
        if ($user instanceof UserInterface) {
            $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
                $user,
                604800 // 7 days
            );
            $this->refreshTokenManager->save($refreshToken);
            $responseData['refresh_token'] = $refreshToken->getRefreshToken();
        }

        $event->setData($responseData);
    }
}
