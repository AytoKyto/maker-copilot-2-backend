<?php

namespace App\Tests\Security;

use App\Security\JWTAuthenticator;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticatorTest extends TestCase
{
    private JWTAuthenticator $authenticator;
    private JWTTokenManagerInterface $jwtManager;
    private UserProviderInterface $userProvider;
    private User $user;

    protected function setUp(): void
    {
        // Create a complete mock of JWTTokenManagerInterface
        $this->jwtManager = $this->getMockBuilder(JWTTokenManagerInterface::class)
            ->onlyMethods(['create', 'decode', 'setUserIdentityField', 'getUserIdentityField', 'getUserIdClaim'])
            ->addMethods(['parse'])  // Add the magic method
            ->getMock();
            
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->authenticator = new JWTAuthenticator($this->jwtManager, $this->userProvider);
        
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setRoles(['ROLE_USER']);
    }

    public function testSupportsWithJwtCookie(): void
    {
        $request = new Request();
        $request->cookies->set('jwt_token', 'fake.jwt.token');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsWithAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer fake.jwt.token');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWithoutToken(): void
    {
        $request = new Request();

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithCookieToken(): void
    {
        $token = 'fake.jwt.token';
        $payload = ['username' => 'test@example.com', 'iat' => time(), 'exp' => time() + 3600];

        $request = new Request();
        $request->cookies->set('jwt_token', $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willReturn($payload);

        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test@example.com')
            ->willReturn($this->user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithHeaderToken(): void
    {
        $token = 'fake.jwt.token';
        $payload = ['username' => 'test@example.com', 'iat' => time(), 'exp' => time() + 3600];

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willReturn($payload);

        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test@example.com')
            ->willReturn($this->user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticatePreferssCookieOverHeader(): void
    {
        $cookieToken = 'cookie.jwt.token';
        $headerToken = 'header.jwt.token';
        $payload = ['username' => 'test@example.com', 'iat' => time(), 'exp' => time() + 3600];

        $request = new Request();
        $request->cookies->set('jwt_token', $cookieToken);
        $request->headers->set('Authorization', 'Bearer ' . $headerToken);

        // Should use cookie token, not header token
        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($cookieToken)
            ->willReturn($payload);

        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test@example.com')
            ->willReturn($this->user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsExceptionWithoutToken(): void
    {
        $request = new Request();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No JWT token found');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWithInvalidToken(): void
    {
        $token = 'invalid.jwt.token';

        $request = new Request();
        $request->cookies->set('jwt_token', $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willThrowException(new \Exception('Invalid token'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid JWT token: Invalid token');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWithEmptyPayload(): void
    {
        $token = 'fake.jwt.token';

        $request = new Request();
        $request->cookies->set('jwt_token', $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid JWT token');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWithoutUsername(): void
    {
        $token = 'fake.jwt.token';
        $payload = ['iat' => time(), 'exp' => time() + 3600]; // Missing username

        $request = new Request();
        $request->cookies->set('jwt_token', $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willReturn($payload);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid JWT token');

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsUnauthorizedResponse(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Test exception');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authentication Required', $response->getContent());
    }

    public function testAuthenticateWithMalformedAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'InvalidFormat token');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No JWT token found');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithBearerButNoToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No JWT token found');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithUserProviderException(): void
    {
        $token = 'fake.jwt.token';
        $payload = ['username' => 'test@example.com', 'iat' => time(), 'exp' => time() + 3600];

        $request = new Request();
        $request->cookies->set('jwt_token', $token);

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with($token)
            ->willReturn($payload);

        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test@example.com')
            ->willThrowException(new \Exception('User not found'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid JWT token: User not found');

        $this->authenticator->authenticate($request);
    }
}