<?php

namespace App\Tests\EventListener;

use App\EventListener\AuthenticationSuccessListener;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListenerTest extends TestCase
{
    private AuthenticationSuccessListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AuthenticationSuccessListener();
    }

    public function testOnAuthenticationSuccessSetsCookies(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token']);

        $event = new AuthenticationSuccessEvent(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token'], $token, $response);

        $this->listener->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        
        $this->assertCount(3, $cookies);

        // Check authenticated cookie
        $authenticatedCookie = $this->findCookieByName($cookies, 'authenticated');
        $this->assertNotNull($authenticatedCookie);
        $this->assertEquals('true', $authenticatedCookie->getValue());
        $this->assertFalse($authenticatedCookie->isHttpOnly());
        $this->assertEquals('lax', $authenticatedCookie->getSameSite());

        // Check JWT token cookie
        $jwtCookie = $this->findCookieByName($cookies, 'jwt_token');
        $this->assertNotNull($jwtCookie);
        $this->assertEquals('fake.jwt.token', $jwtCookie->getValue());
        $this->assertTrue($jwtCookie->isHttpOnly());
        $this->assertEquals('lax', $jwtCookie->getSameSite());

        // Check refresh token cookie
        $refreshCookie = $this->findCookieByName($cookies, 'refresh_token');
        $this->assertNotNull($refreshCookie);
        $this->assertEquals('fake.refresh.token', $refreshCookie->getValue());
        $this->assertTrue($refreshCookie->isHttpOnly());
        $this->assertEquals('lax', $refreshCookie->getSameSite());
    }

    public function testOnAuthenticationSuccessWithoutJwtToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['refresh_token' => 'fake.refresh.token']);

        $event = new AuthenticationSuccessEvent(['refresh_token' => 'fake.refresh.token'], $token, $response);

        $this->listener->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();

        // Should still set authenticated and refresh token cookies, but not JWT cookie
        $this->assertCount(2, $cookies);

        $authenticatedCookie = $this->findCookieByName($cookies, 'authenticated');
        $this->assertNotNull($authenticatedCookie);

        $jwtCookie = $this->findCookieByName($cookies, 'jwt_token');
        $this->assertNull($jwtCookie);

        $refreshCookie = $this->findCookieByName($cookies, 'refresh_token');
        $this->assertNotNull($refreshCookie);
    }

    public function testOnAuthenticationSuccessWithoutRefreshToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['token' => 'fake.jwt.token']);

        $event = new AuthenticationSuccessEvent(['token' => 'fake.jwt.token'], $token, $response);

        $this->listener->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();

        // Should set authenticated and JWT cookies, but not refresh token cookie
        $this->assertCount(2, $cookies);

        $authenticatedCookie = $this->findCookieByName($cookies, 'authenticated');
        $this->assertNotNull($authenticatedCookie);

        $jwtCookie = $this->findCookieByName($cookies, 'jwt_token');
        $this->assertNotNull($jwtCookie);

        $refreshCookie = $this->findCookieByName($cookies, 'refresh_token');
        $this->assertNull($refreshCookie);
    }

    public function testOnAuthenticationSuccessWithNonUserInterface(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token']);

        $event = new AuthenticationSuccessEvent(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token'], $token, $response);

        $this->listener->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        
        $this->assertCount(3, $cookies);

        // Should work with any UserInterface implementation
        $authenticatedCookie = $this->findCookieByName($cookies, 'authenticated');
        $this->assertNotNull($authenticatedCookie);
        $this->assertEquals('true', $authenticatedCookie->getValue());
    }

    public function testCookieExpirationTimes(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token']);

        $beforeTime = time();
        $event = new AuthenticationSuccessEvent(['token' => 'fake.jwt.token', 'refresh_token' => 'fake.refresh.token'], $token, $response);
        $this->listener->onAuthenticationSuccess($event);
        $afterTime = time();

        $cookies = $response->headers->getCookies();

        $jwtCookie = $this->findCookieByName($cookies, 'jwt_token');
        $refreshCookie = $this->findCookieByName($cookies, 'refresh_token');

        // JWT cookie should expire in 24 hours (86400 seconds)
        $expectedJwtExpiry = $beforeTime + 86400;
        $this->assertGreaterThanOrEqual($expectedJwtExpiry, $jwtCookie->getExpiresTime());
        $this->assertLessThanOrEqual($expectedJwtExpiry + ($afterTime - $beforeTime), $jwtCookie->getExpiresTime());

        // Refresh token cookie should expire in 30 days (2592000 seconds)
        $expectedRefreshExpiry = $beforeTime + 2592000;
        $this->assertGreaterThanOrEqual($expectedRefreshExpiry, $refreshCookie->getExpiresTime());
        $this->assertLessThanOrEqual($expectedRefreshExpiry + ($afterTime - $beforeTime), $refreshCookie->getExpiresTime());
    }

    public function testCookieSecurityAttributes(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = new Request();
        $response = new JsonResponse(['token' => 'fake.jwt.token']);

        $event = new AuthenticationSuccessEvent(['token' => 'fake.jwt.token'], $token, $response);

        $this->listener->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();

        $authenticatedCookie = $this->findCookieByName($cookies, 'authenticated');
        $jwtCookie = $this->findCookieByName($cookies, 'jwt_token');

        // Authenticated cookie should NOT be httpOnly (accessible to JS)
        $this->assertFalse($authenticatedCookie->isHttpOnly());
        $this->assertEquals('lax', $authenticatedCookie->getSameSite());

        // JWT cookie should be httpOnly and secure
        $this->assertTrue($jwtCookie->isHttpOnly());
        $this->assertEquals('lax', $jwtCookie->getSameSite());
    }

    private function findCookieByName(array $cookies, string $name): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }
        return null;
    }
}