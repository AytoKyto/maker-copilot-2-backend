<?php

declare(strict_types=1);

namespace App\Service\Validation;

use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class EmailUserExistValidation
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validateEmailUserExists(string $email)
    {
        // Vérifier que l'email est fourni
        if (!$email) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Rechercher l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $user;
    }
}
