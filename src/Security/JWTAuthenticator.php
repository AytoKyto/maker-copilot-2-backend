<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * JWT Authenticator
 *
 * Authenticates API requests using JWT tokens from Authorization header.
 * Expects Bearer token format: "Authorization: Bearer {token}"
 *
 * @package App\Security
 */
class JWTAuthenticator extends AbstractAuthenticator
{
    private JWTTokenManagerInterface $jwtManager;
    private UserProviderInterface $userProvider;

    /**
     * Constructor
     *
     * @param JWTTokenManagerInterface $jwtManager JWT token manager
     * @param UserProviderInterface $userProvider User provider
     */
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserProviderInterface $userProvider
    ) {
        $this->jwtManager = $jwtManager;
        $this->userProvider = $userProvider;
    }

    /**
     * Check if request has Authorization header
     *
     * @param Request $request HTTP request
     *
     * @return bool|null True if Authorization header present
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    /**
     * Authenticate request using JWT from Authorization header
     *
     * @param Request $request HTTP request
     *
     * @return Passport Authentication passport
     *
     * @throws AuthenticationException When token is missing or invalid
     */
    public function authenticate(Request $request): Passport
    {
        $token = $this->extractTokenFromHeader($request);

        if (!$token) {
            throw new AuthenticationException('No JWT token found in Authorization header');
        }

        try {
            $payload = $this->jwtManager->parse($token);

            if (!$payload || !isset($payload['username'])) {
                throw new AuthenticationException('Invalid JWT token payload');
            }

            return new SelfValidatingPassport(
                new UserBadge($payload['username'], function (string $userIdentifier) {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful authentication
     *
     * @param Request $request HTTP request
     * @param TokenInterface $token Security token
     * @param string $firewallName Firewall name
     *
     * @return Response|null Null to continue request processing
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    /**
     * Handle authentication failure
     *
     * @param Request $request HTTP request
     * @param AuthenticationException $exception Authentication exception
     *
     * @return Response JSON error response
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse([
            'error' => 'unauthorized',
            'message' => 'Authentication required'
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Extract JWT token from Authorization header
     *
     * @param Request $request HTTP request
     *
     * @return string|null Token string or null if not found
     */
    private function extractTokenFromHeader(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
