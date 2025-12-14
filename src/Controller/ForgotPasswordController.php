<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Mail\EmailService;
use App\Service\Validation\EmailUserExistValidation;
use App\Service\PasswordResetTokenService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Forgot Password Controller
 *
 * Handles password reset flow for users who forgot their password.
 * Generates reset tokens and sends email with reset link.
 *
 * @package App\Controller
 */
class ForgotPasswordController extends AbstractController
{
    private EmailService $emailService;
    private EmailUserExistValidation $emailUserExistValidation;
    private PasswordResetTokenService $passwordResetTokenService;
    private UrlGeneratorInterface $urlGenerator;
    private string $frontendUrl;
    private string $noReplyEmail;

    /**
     * Constructor
     *
     * @param EmailService $emailService Email sending service
     * @param EmailUserExistValidation $emailUserExistValidation Email validation service
     * @param PasswordResetTokenService $passwordResetTokenService Token management service
     * @param UrlGeneratorInterface $urlGenerator URL generator
     * @param ParameterBagInterface $params Application parameters
     */
    public function __construct(
        EmailService $emailService,
        EmailUserExistValidation $emailUserExistValidation,
        PasswordResetTokenService $passwordResetTokenService,
        UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $params
    ) {
        $this->emailService = $emailService;
        $this->emailUserExistValidation = $emailUserExistValidation;
        $this->passwordResetTokenService = $passwordResetTokenService;
        $this->urlGenerator = $urlGenerator;
        $this->frontendUrl = $params->get('frontend_url');
        $this->noReplyEmail = $params->get('no_reply_email');
    }

    #[Route('/api/forgot-password', name: 'forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        try {
            // Validation de l'e-mail et récupération de l'utilisateur
            $user = $this->emailUserExistValidation->validateEmailUserExists($email);

            // Create reset token
            $resetToken = $this->passwordResetTokenService->createPasswordResetToken($user);

            // Generate reset URL
            $resetUrl = $this->frontendUrl . '/reset-password?token=' . $resetToken->getToken();

            // Send email with reset link
            $htmlContent = 'email/password_reset_link.html.twig';
            $contextEmail = [
                'resetUrl' => $resetUrl,
                'userEmail' => $user->getEmail(),
                'expirationTime' => '1 heure'
            ];

            $this->emailService->sendEmail(
                $this->noReplyEmail,
                $user->getEmail(),
                'Réinitialisation de votre mot de passe',
                $htmlContent,
                $contextEmail
            );

            // Message générique pour éviter l'énumération d'emails
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Si un compte existe avec cette adresse email, un lien de réinitialisation a été envoyé.'
            ], JsonResponse::HTTP_OK);
            
        } catch (\Exception $e) {
            // Message générique en cas d'erreur aussi
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Si un compte existe avec cette adresse email, un lien de réinitialisation a été envoyé.'
            ], JsonResponse::HTTP_OK);
        }
    }

    #[Route('/api/validate-reset-token', name: 'validate-reset-token', methods: ['POST'])]
    public function validateResetToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tokenString = $data['token'] ?? null;

        if (!$tokenString) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token manquant'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $this->passwordResetTokenService->validateToken($tokenString);

        if (!$token) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token invalide ou expiré'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Token valide',
            'email' => $token->getUser()->getEmail()
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/api/reset-password-with-token', name: 'reset-password-with-token', methods: ['POST'])]
    public function resetPasswordWithToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tokenString = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$tokenString || !$newPassword) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token et mot de passe requis'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 8) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Le mot de passe doit contenir au moins 8 caractères'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $success = $this->passwordResetTokenService->resetPassword($tokenString, $newPassword);

        if (!$success) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token invalide ou expiré'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Mot de passe réinitialisé avec succès'
        ], JsonResponse::HTTP_OK);
    }
}
