<?php

declare(strict_types=1);
// src/Controller/RegistrationController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Subscription;
use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;
use App\Service\RecaptchaValidator;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Registration Controller
 *
 * Handles user registration with validation and JWT token generation.
 * Creates user account and starter subscription.
 *
 * @package App\Controller
 */
class RegistrationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private RecaptchaValidator $recaptchaValidator;
    private RefreshTokenGeneratorInterface $refreshTokenGenerator;
    private RefreshTokenManagerInterface $refreshTokenManager;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hasher
     * @param JWTTokenManagerInterface $jwtManager JWT manager
     * @param ValidatorInterface $validator Validator service
     * @param LoggerInterface $logger Logger service
     * @param RecaptchaValidator $recaptchaValidator reCAPTCHA validator
     * @param RefreshTokenGeneratorInterface $refreshTokenGenerator Refresh token generator
     * @param RefreshTokenManagerInterface $refreshTokenManager Refresh token manager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        RecaptchaValidator $recaptchaValidator,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->recaptchaValidator = $recaptchaValidator;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    #[Route('/register', name: 'register', methods: 'POST')]
    public function register(Request $request, MailerInterface $mailer): JsonResponse
    {
        try {
            $time = new \DateTimeImmutable();
            $data = json_decode($request->getContent(), true);

            // Validation des données d'entrée
            if (!$data) {
                return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification de la présence des champs requis
            if (!isset($data['email']) || !isset($data['password'])) {
                return new JsonResponse(['error' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
            }

            // Validation du token reCAPTCHA
            if (!isset($data['recaptcha_token']) || empty($data['recaptcha_token'])) {
                return new JsonResponse(['error' => 'Token reCAPTCHA requis'], Response::HTTP_BAD_REQUEST);
            }

            $clientIp = $request->getClientIp() ?? '127.0.0.1';
            $recaptchaResult = $this->recaptchaValidator->validateRegister($data['recaptcha_token'], $clientIp);

            if (!$recaptchaResult['success']) {
                $this->logger->warning('reCAPTCHA validation failed', [
                    'ip' => $clientIp,
                    'errors' => $recaptchaResult['errors'],
                    'score' => $recaptchaResult['score']
                ]);
                return new JsonResponse(['error' => 'Validation de sécurité échouée'], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier le score reCAPTCHA (optionnel, pour plus de sécurité)
            if ($recaptchaResult['score'] < 0.5) {
                $this->logger->warning('reCAPTCHA score too low', [
                    'ip' => $clientIp,
                    'score' => $recaptchaResult['score']
                ]);
                return new JsonResponse(['error' => 'Activité suspecte détectée'], Response::HTTP_BAD_REQUEST);
            }

            // Validation de l'email
            $emailConstraint = new Assert\Email(['message' => 'Email invalide']);
            $emailErrors = $this->validator->validate($data['email'], $emailConstraint);
            
            if (count($emailErrors) > 0) {
                return new JsonResponse(['error' => 'Format d\'email invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validation du mot de passe (minimum 8 caractères, au moins une lettre et un chiffre)
            $passwordConstraints = [
                new Assert\NotBlank(['message' => 'Le mot de passe ne peut pas être vide']),
                new Assert\Length([
                    'min' => 8,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                ]),
                new Assert\Regex([
                    'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).+$/',
                    'message' => 'Le mot de passe doit contenir au moins une lettre et un chiffre'
                ])
            ];

            $passwordErrors = $this->validator->validate($data['password'], $passwordConstraints);
            
            if (count($passwordErrors) > 0) {
                $errorMessages = [];
                foreach ($passwordErrors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => implode('. ', $errorMessages)], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'email existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            // Création de l'utilisateur
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            $user->setCreatedAt($time);
            $user->setUpdatedAt($time);
            $user->setTypeSubscription(0);
            $user->setAbatementPourcent(0);
            $user->setRoles(['ROLE_USER']);
            
            // Définir les valeurs URSSAF par défaut
            $user->setUrssafPourcent(0);
            $user->setUrssafType(0);
            $user->setObjectifValue(0);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Créer automatiquement une subscription gratuite pour le nouvel utilisateur
            try {
                $starterPlan = $this->entityManager->getRepository(Plan::class)->findOneBy(['slug' => 'starter']);
                if ($starterPlan) {
                    $subscription = new Subscription();
                    $subscription->setUser($user);
                    $subscription->setPlan($starterPlan);
                    $subscription->setStripeSubscriptionId('sub_free_' . uniqid() . '_' . $user->getId());
                    $subscription->setStatus('active');
                    $subscription->setBillingInterval('month');
                    $subscription->setAmount(0);
                    $subscription->setCurrency('EUR');
                    $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
                    $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('+30 days'));
                    $subscription->setCreatedAt(new \DateTimeImmutable());
                    $subscription->setUpdatedAt(new \DateTimeImmutable());
                    
                    $this->entityManager->persist($subscription);
                    $this->entityManager->flush();
                    
                    $this->logger->info('Subscription gratuite créée pour l\'utilisateur ' . $user->getEmail());
                }
            } catch (\Exception $subException) {
                // Log l'erreur mais ne bloque pas la création du compte
                $this->logger->error('Erreur lors de la création de la subscription gratuite: ' . $subException->getMessage());
            }

            // Generate JWT access token
            $jwt = $this->jwtManager->create($user);

            // Generate refresh token
            $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
                $user,
                604800 // 7 days
            );
            $this->refreshTokenManager->save($refreshToken);

            // Send welcome email (don't block on error)
            try {
                $htmlContent = $this->renderView('email/welcome.html.twig', [
                    'email' => $user->getEmail()
                ]);

                $email = (new Email())
                    ->from('no-reply@maker-copilot.com')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Maker Copilot!')
                    ->html($htmlContent);

                $mailer->send($email);
                $this->logger->info('Welcome email sent to ' . $user->getEmail());
            } catch (\Exception $emailException) {
                $this->logger->error('Error sending welcome email: ' . $emailException->getMessage());
            }

            // Return tokens in same format as login
            return new JsonResponse([
                'access_token' => $jwt,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'message' => 'Account created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du compte: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la création du compte'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
