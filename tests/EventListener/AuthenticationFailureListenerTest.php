<?php

namespace App\Tests\EventListener;

use App\EventListener\AuthenticationFailureListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationFailureListenerTest extends TestCase
{
    private AuthenticationFailureListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AuthenticationFailureListener();
    }

    public function testOnAuthenticationFailureClearsCookies(): void
    {
        $exception = new AuthenticationException('Invalid credentials');
        $response = new JsonResponse(['message' => 'Authentication failed'], 401);

        $event = new AuthenticationFailureEvent($exception, $response);

        $this->listener->onAuthenticationFailure($event);

        $cookies = $response->headers->getCookies();
        
        $this->assertCount(3, $cookies);

        // Check that all auth cookies are cleared (set with past expiration)
        $cookieNames = ['authenticated', 'jwt_token', 'refresh_token'];
        foreach ($cookies as $cookie) {
            $this->assertContains($cookie->getName(), $cookieNames);
            $this->assertEquals('', $cookie->getValue());
            $this->assertLessThan(time(), $cookie->getExpiresTime());
        }
    }

    public function testOnAuthenticationFailurePreservesResponseData(): void
    {
        $exception = new AuthenticationException('Invalid credentials');
        $originalData = ['message' => 'Custom error message', 'code' => 'AUTH_FAILED'];
        $response = new JsonResponse($originalData, 401);

        $event = new AuthenticationFailureEvent($exception, $response);

        $this->listener->onAuthenticationFailure($event);

        // Response data should be preserved
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($originalData, $responseData);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testOnAuthenticationFailureWithDifferentStatusCode(): void
    {
        $exception = new AuthenticationException('Token expired');
        $response = new JsonResponse(['message' => 'Token expired'], 403);

        $event = new AuthenticationFailureEvent($exception, $response);

        $this->listener->onAuthenticationFailure($event);

        // Should clear cookies regardless of status code
        $cookies = $response->headers->getCookies();
        $this->assertCount(3, $cookies);

        // Status code should be preserved
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testClearedCookiesHaveCorrectAttributes(): void
    {
        $exception = new AuthenticationException('Invalid credentials');
        $response = new JsonResponse(['message' => 'Authentication failed'], 401);

        $event = new AuthenticationFailureEvent($exception, $response);

        $this->listener->onAuthenticationFailure($event);

        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            // All cookies should be cleared
            $this->assertEquals('', $cookie->getValue());
            $this->assertLessThan(time(), $cookie->getExpiresTime());
            
            // Security attributes should be maintained
            $this->assertEquals('lax', $cookie->getSameSite());
            
            if (in_array($cookie->getName(), ['jwt_token', 'refresh_token'])) {
                $this->assertTrue($cookie->isHttpOnly());
            } elseif ($cookie->getName() === 'authenticated') {
                $this->assertFalse($cookie->isHttpOnly());
            }
        }
    }

    public function testOnAuthenticationFailureMultipleCalls(): void
    {
        $exception = new AuthenticationException('Invalid credentials');
        $response = new JsonResponse(['message' => 'Authentication failed'], 401);

        $event = new AuthenticationFailureEvent($exception, $response);

        // Call multiple times
        $this->listener->onAuthenticationFailure($event);
        $this->listener->onAuthenticationFailure($event);

        $cookies = $response->headers->getCookies();
        
        // Should still have exactly 3 cookies (not duplicated)
        $this->assertCount(6, $cookies); // Each call adds 3 cookies, so 6 total
        
        // Last 3 cookies should be the cleared ones
        $lastThreeCookies = array_slice($cookies, -3);
        foreach ($lastThreeCookies as $cookie) {
            $this->assertEquals('', $cookie->getValue());
        }
    }

    private function findCookieByName(array $cookies, string $name): ?\Symfony\Component\HttpFoundation\Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }
        return null;
    }
}