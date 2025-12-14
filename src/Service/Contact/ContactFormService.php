<?php

declare(strict_types=1);

namespace App\Service\Contact;

use App\Service\Mail\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Contact Form Service
 *
 * Handles processing and email sending for contact form submissions.
 * Validates input and sends notifications to admin and confirmation to user.
 *
 * @package App\Service\Contact
 */
class ContactFormService
{
    private EmailService $emailService;
    private LoggerInterface $logger;
    private string $adminEmail;
    private string $secondaryEmail;
    private string $noReplyEmail;

    /**
     * Constructor
     *
     * @param EmailService $emailService Email sending service
     * @param LoggerInterface $logger Logger service
     * @param ParameterBagInterface $params Application parameters
     */
    public function __construct(
        EmailService $emailService,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->adminEmail = $params->get('contact_admin_email');
        $this->secondaryEmail = $params->get('contact_secondary_email');
        $this->noReplyEmail = $params->get('no_reply_email');
    }

    /**
     * Process contact form submission
     *
     * Validates data, sends user confirmation and admin notification.
     *
     * @param array{
     *   prenom: string,
     *   nom: string,
     *   email: string,
     *   message: string,
     *   typeActivite?: string,
     *   tailleEntreprise?: string
     * } $data Contact form data
     * @param string $formType Type of form (home, testeur)
     *
     * @return bool True on success
     *
     * @throws \InvalidArgumentException When validation fails
     */
    public function processContactForm(array $data, string $formType = 'home'): bool
    {
        // Validate required fields
        $requiredFields = ['prenom', 'nom', 'email', 'message'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Sanitize data
        $sanitizedData = $this->sanitizeData($data);

        // Send user confirmation
        $this->sendUserConfirmation($sanitizedData);

        // Send admin notification
        $this->sendAdminNotification($sanitizedData, $formType);

        $this->logger->info('Contact form processed', [
            'email' => $data['email'],
            'form_type' => $formType
        ]);

        return true;
    }

    /**
     * Sanitize contact form data
     *
     * @param array $data Raw form data
     *
     * @return array Sanitized data
     */
    private function sanitizeData(array $data): array
    {
        return [
            'prenom' => htmlspecialchars($data['prenom'], ENT_QUOTES, 'UTF-8'),
            'nom' => htmlspecialchars($data['nom'], ENT_QUOTES, 'UTF-8'),
            'email' => $data['email'],
            'message' => htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8'),
            'typeActivite' => isset($data['typeActivite'])
                ? htmlspecialchars($data['typeActivite'], ENT_QUOTES, 'UTF-8')
                : null,
            'tailleEntreprise' => isset($data['tailleEntreprise'])
                ? htmlspecialchars($data['tailleEntreprise'], ENT_QUOTES, 'UTF-8')
                : null,
        ];
    }

    /**
     * Send confirmation email to user
     *
     * @param array $data Sanitized form data
     *
     * @return void
     */
    private function sendUserConfirmation(array $data): void
    {
        $this->emailService->sendEmail(
            $this->noReplyEmail,
            $data['email'],
            'Confirmation de réception de votre demande sur Maker Copilot',
            'email/contact_email.html.twig',
            $data
        );
    }

    /**
     * Send notification email to admin
     *
     * @param array $data Sanitized form data
     * @param string $formType Type of form
     *
     * @return void
     */
    private function sendAdminNotification(array $data, string $formType): void
    {
        $template = $formType === 'testeur'
            ? 'email/contact_testeur_me.html.twig'
            : 'email/contact_email_me.html.twig';

        $this->emailService->sendEmail(
            $this->noReplyEmail,
            $this->adminEmail,
            'Message formulaire de contact ' . $formType . ' Maker Copilot',
            $template,
            $data
        );
    }
}
