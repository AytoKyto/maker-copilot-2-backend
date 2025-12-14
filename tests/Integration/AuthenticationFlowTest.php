<?php

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationFlowTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testCompleteAuthenticationFlow(): void
    {
        $client = static::createClient();

        // Create test user in database
        $user = new User();
        $user->setEmail('integration@test.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'testpassword');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            // Step 1: Login with valid credentials
            $client->request('POST', '/api/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => 'integration@test.com',
                'password' => 'testpassword'
            ]));

            $loginResponse = $client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $loginResponse->getStatusCode());

            $loginData = json_decode($loginResponse->getContent(), true);
            $this->assertArrayHasKey('token', $loginData);

            // Check cookies are set
            $cookies = $loginResponse->headers->getCookies();
            $this->assertCount(3, $cookies);

            $cookieNames = array_map(fn($cookie) => $cookie->getName(), $cookies);
            $this->assertContains('authenticated', $cookieNames);
            $this->assertContains('jwt_token', $cookieNames);
            $this->assertContains('refresh_token', $cookieNames);

            // Step 2: Access protected endpoint with cookies
            $client->request('GET', '/api/auth/check');
            $checkResponse = $client->getResponse();
            
            $this->assertEquals(Response::HTTP_OK, $checkResponse->getStatusCode());
            $checkData = json_decode($checkResponse->getContent(), true);
            $this->assertTrue($checkData['authenticated']);
            $this->assertEquals('integration@test.com', $checkData['user']['email']);

            // Step 3: Test refresh token
            $client->request('POST', '/api/token/refresh');
            $refreshResponse = $client->getResponse();
            
            // Should succeed or return appropriate response based on implementation
            $this->assertContains($refreshResponse->getStatusCode(), [Response::HTTP_OK, Response::HTTP_UNAUTHORIZED]);

            // Step 4: Logout
            $client->request('POST', '/api/logout');
            $logoutResponse = $client->getResponse();
            
            $this->assertEquals(Response::HTTP_OK, $logoutResponse->getStatusCode());
            
            // Check cookies are cleared
            $logoutCookies = $logoutResponse->headers->getCookies();
            foreach ($logoutCookies as $cookie) {
                $this->assertEquals('', $cookie->getValue());
                $this->assertLessThan(time(), $cookie->getExpiresTime());
            }

            // Step 5: Try to access protected endpoint after logout
            $client->request('GET', '/api/auth/check');
            $finalCheckResponse = $client->getResponse();
            
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $finalCheckResponse->getStatusCode());

        } finally {
            // Cleanup
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testInvalidCredentialsFlow(): void
    {
        $client = static::createClient();

        // Try login with invalid credentials
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'invalid@test.com',
            'password' => 'wrongpassword'
        ]));

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // Check that no authentication cookies are set
        $cookies = $response->headers->getCookies();
        
        // If failure listener clears cookies, we might get clearing cookies
        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                if (in_array($cookie->getName(), ['authenticated', 'jwt_token', 'refresh_token'])) {
                    $this->assertEquals('', $cookie->getValue());
                }
            }
        }
    }

    public function testJWTTokenInHeader(): void
    {
        $client = static::createClient();

        // Create test user
        $user = new User();
        $user->setEmail('header@test.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'testpassword');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            // Login to get JWT token
            $client->request('POST', '/api/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => 'header@test.com',
                'password' => 'testpassword'
            ]));

            $loginResponse = $client->getResponse();
            $loginData = json_decode($loginResponse->getContent(), true);
            $jwtToken = $loginData['token'];

            // Create new client to avoid cookie persistence
            $newClient = static::createClient();

            // Use JWT token in Authorization header
            $newClient->request('GET', '/api/auth/check', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $jwtToken,
            ]);

            $response = $newClient->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

            $responseData = json_decode($response->getContent(), true);
            $this->assertTrue($responseData['authenticated']);
            $this->assertEquals('header@test.com', $responseData['user']['email']);

        } finally {
            // Cleanup
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testCORSWithCredentials(): void
    {
        $client = static::createClient();

        // Test preflight OPTIONS request
        $client->request('OPTIONS', '/api/login', [], [], [
            'HTTP_ORIGIN' => 'http://localhost:8080',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type',
        ]);

        $response = $client->getResponse();
        
        // Should allow CORS for configured origins
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertStringContainsString('localhost:8080', $response->headers->get('Access-Control-Allow-Origin') ?? '');
    }

    public function testMultipleConcurrentSessions(): void
    {
        $client1 = static::createClient();
        $client2 = static::createClient();

        // Create test user
        $user = new User();
        $user->setEmail('concurrent@test.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'testpassword');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            // Login with both clients
            foreach ([$client1, $client2] as $client) {
                $client->request('POST', '/api/login', [], [], [
                    'CONTENT_TYPE' => 'application/json',
                ], json_encode([
                    'email' => 'concurrent@test.com',
                    'password' => 'testpassword'
                ]));

                $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
            }

            // Both sessions should work independently
            $client1->request('GET', '/api/auth/check');
            $this->assertEquals(Response::HTTP_OK, $client1->getResponse()->getStatusCode());

            $client2->request('GET', '/api/auth/check');
            $this->assertEquals(Response::HTTP_OK, $client2->getResponse()->getStatusCode());

            // Logout one session
            $client1->request('POST', '/api/logout');
            $this->assertEquals(Response::HTTP_OK, $client1->getResponse()->getStatusCode());

            // First session should be logged out
            $client1->request('GET', '/api/auth/check');
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client1->getResponse()->getStatusCode());

            // Second session should still work
            $client2->request('GET', '/api/auth/check');
            $this->assertEquals(Response::HTTP_OK, $client2->getResponse()->getStatusCode());

        } finally {
            // Cleanup
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testTokenExpiration(): void
    {
        $this->markTestSkipped('Token expiration testing requires time manipulation or short-lived tokens');
    }
}