<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    public function testLogoutClearsCookies(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/logout');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => 'Logged out successfully'], $responseData);
        
        // Check that cookies are cleared
        $cookies = $response->headers->getCookies();
        $this->assertCount(3, $cookies);
        
        $cookieNames = [];
        foreach ($cookies as $cookie) {
            $cookieNames[] = $cookie->getName();
            $this->assertEquals('', $cookie->getValue());
            $this->assertLessThan(time(), $cookie->getExpiresTime());
            $this->assertEquals('/', $cookie->getPath());
            $this->assertEquals('lax', $cookie->getSameSite());
        }
        
        $this->assertContains('authenticated', $cookieNames);
        $this->assertContains('jwt_token', $cookieNames);
        $this->assertContains('refresh_token', $cookieNames);
    }

    public function testLogoutCookieAttributes(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/logout');
        
        $response = $client->getResponse();
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'authenticated') {
                $this->assertFalse($cookie->isHttpOnly());
                $this->assertFalse($cookie->isSecure()); // false in test environment
            } elseif (in_array($cookie->getName(), ['jwt_token', 'refresh_token'])) {
                $this->assertTrue($cookie->isHttpOnly());
                $this->assertFalse($cookie->isSecure()); // false in test environment
            }
        }
    }

    public function testAuthCheckWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/auth/check');
        
        $response = $client->getResponse();
        
        // Should return 401 as endpoint requires authentication
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testAuthCheckWithAuthentication(): void
    {
        $client = static::createClient();
        
        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        
        // Mock authentication by logging in the user
        $client->loginUser($user);
        
        $client->request('GET', '/api/auth/check');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        
        $this->assertTrue($responseData['authenticated']);
        $this->assertEquals('test@example.com', $responseData['user']['email']);
        $this->assertEquals(['ROLE_USER'], $responseData['user']['roles']);
    }

    public function testAuthCheckWithAdminUser(): void
    {
        $client = static::createClient();
        
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        
        $client->loginUser($user);
        
        $client->request('GET', '/api/auth/check');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        
        $this->assertTrue($responseData['authenticated']);
        $this->assertEquals('admin@example.com', $responseData['user']['email']);
        $this->assertContains('ROLE_USER', $responseData['user']['roles']);
        $this->assertContains('ROLE_ADMIN', $responseData['user']['roles']);
    }

    public function testLogoutMethodsAllowed(): void
    {
        $client = static::createClient();
        
        // Test that only POST is allowed for logout
        $client->request('GET', '/api/logout');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        
        $client->request('PUT', '/api/logout');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        
        $client->request('DELETE', '/api/logout');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testAuthCheckMethodsAllowed(): void
    {
        $client = static::createClient();
        
        // Test that only GET is allowed for auth check
        $client->request('POST', '/api/auth/check');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        
        $client->request('PUT', '/api/auth/check');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        
        $client->request('DELETE', '/api/auth/check');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testLogoutResponseHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/logout');
        
        $response = $client->getResponse();
        
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertTrue($response->headers->has('Set-Cookie'));
    }

    public function testAuthCheckResponseStructure(): void
    {
        $client = static::createClient();
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        
        $client->loginUser($user);
        
        $client->request('GET', '/api/auth/check');
        
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        
        // Verify response structure
        $this->assertArrayHasKey('authenticated', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('email', $responseData['user']);
        $this->assertArrayHasKey('roles', $responseData['user']);
        
        $this->assertIsBool($responseData['authenticated']);
        $this->assertIsArray($responseData['user']['roles']);
        $this->assertIsString($responseData['user']['email']);
    }

    public function testMultipleLogoutCalls(): void
    {
        $client = static::createClient();
        
        // First logout
        $client->request('POST', '/api/logout');
        $response1 = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response1->getStatusCode());
        
        // Second logout should still work
        $client->request('POST', '/api/logout');
        $response2 = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response2->getStatusCode());
        
        // Response should be identical
        $this->assertEquals($response1->getContent(), $response2->getContent());
    }
}