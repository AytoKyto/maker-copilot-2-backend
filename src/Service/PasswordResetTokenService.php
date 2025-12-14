<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetTokenService
{
    private EntityManagerInterface $entityManager;
    private PasswordResetTokenRepository $tokenRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        PasswordResetTokenRepository $tokenRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function createPasswordResetToken(User $user): PasswordResetToken
    {
        // Invalidate any existing tokens for this user
        $existingTokens = $this->tokenRepository->findBy(['user' => $user, 'usedAt' => null]);
        foreach ($existingTokens as $token) {
            $token->setUsedAt(new \DateTimeImmutable());
        }

        // Create new token
        $token = new PasswordResetToken();
        $token->setUser($user);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function validateToken(string $tokenString): ?PasswordResetToken
    {
        $token = $this->tokenRepository->findValidToken($tokenString);
        
        if (!$token || !$token->isValid()) {
            return null;
        }

        return $token;
    }

    public function resetPassword(string $tokenString, string $newPassword): bool
    {
        $token = $this->validateToken($tokenString);
        
        if (!$token) {
            return false;
        }

        $user = $token->getUser();
        
        // Hash and set the new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Mark token as used
        $token->setUsedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        return true;
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->removeExpiredTokens();
    }
}