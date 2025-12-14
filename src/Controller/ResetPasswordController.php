<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\User;

/**
 * Reset Password Controller
 *
 * Handles password reset for authenticated users.
 * Updates password in database and sends confirmation email.
 *
 * @package App\Controller
 */
class ResetPasswordController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private string $noReplyEmail;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hashing service
     * @param ParameterBagInterface $params Application parameters
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->noReplyEmail = $params->get('no_reply_email');
    }

    /**
     * Reset password for authenticated user
     *
     * Validates input, hashes new password, updates user and sends confirmation email.
     *
     * @param MailerInterface $mailer Mailer service
     * @param Request $request HTTP request
     *
     * @return JsonResponse Success or error response
     */
    #[Route('/api/reset-password', name: 'reset-password', methods: ['POST'])]
    public function resetPassword(MailerInterface $mailer, Request $request): JsonResponse
    {
        // Get currently authenticated user
        $user = $this->getUser();

        // Check if user is authenticated
        if (!$user || !$user instanceof User) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Decode and validate JSON payload
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['password']) || empty($data['password'])) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Password is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $password = $data['password'];

        // Validate password strength (minimum 8 characters)
        if (strlen($password) < 8) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Password must be at least 8 characters'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Hash new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);

        // Update user with new password
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Render HTML content from Twig template
        $htmlContent = $this->renderView('email/info_password_email.html.twig', []);

        // Create password reset confirmation email
        $email = (new Email())
            ->from($this->noReplyEmail)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($htmlContent);

        // Send email
        try {
            $mailer->send($email);

            return new JsonResponse(
                ['status' => 'success', 'message' => 'Password updated successfully'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            // Log error but don't expose details to client
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Password updated but failed to send confirmation email'],
                Response::HTTP_OK
            );
        }
    }
}
