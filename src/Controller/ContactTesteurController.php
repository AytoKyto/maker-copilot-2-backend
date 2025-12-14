<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Contact Testeur Controller
 *
 * Handles contact form submissions from tester request page.
 * Sends confirmation email to user and notification to admin.
 *
 * @package App\Controller
 */
class ContactTesteurController extends AbstractController
{
    private string $adminEmail;
    private string $secondaryEmail;
    private string $noReplyEmail;

    /**
     * Constructor
     *
     * @param ParameterBagInterface $params Application parameters
     */
    public function __construct(ParameterBagInterface $params)
    {
        $this->adminEmail = $params->get('contact_admin_email');
        $this->secondaryEmail = $params->get('contact_secondary_email');
        $this->noReplyEmail = $params->get('no_reply_email');
    }

    /**
     * Send contact email from tester form
     *
     * Validates input data, sends confirmation to user and notification to admin.
     *
     * @param MailerInterface $mailer Mailer service
     * @param Request $request HTTP request
     *
     * @return JsonResponse Success or error response
     */
    #[Route('/api/contact-testeur', name: 'contact-testeur', methods: ['POST'])]
    public function sendContactEmail(MailerInterface $mailer, Request $request): JsonResponse
    {
        // Decode and validate JSON payload
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Invalid JSON payload'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate required fields
        $requiredFields = ['prenom', 'nom', 'email', 'message', 'typeActivite', 'tailleEntreprise'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new JsonResponse(
                    ['status' => 'error', 'message' => "Missing required field: {$field}"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Invalid email format'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $prenom = htmlspecialchars($data['prenom'], ENT_QUOTES, 'UTF-8');
        $nom = htmlspecialchars($data['nom'], ENT_QUOTES, 'UTF-8');
        $email = $data['email'];
        $message = htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8');
        $typeActivite = htmlspecialchars($data['typeActivite'], ENT_QUOTES, 'UTF-8');
        $tailleEntreprise = htmlspecialchars($data['tailleEntreprise'], ENT_QUOTES, 'UTF-8');

        // Render HTML content for user confirmation email
        $htmlContentUser = $this->renderView('email/contact_email.html.twig', []);

        // Render HTML content for admin notification email
        $htmlContentMe = $this->renderView('email/contact_testeur_me.html.twig', [
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'message' => $message,
            'typeActivite' => $typeActivite,
            'tailleEntreprise' => $tailleEntreprise,
        ]);

        // Create user confirmation email
        $userEmail = (new Email())
            ->from($this->noReplyEmail)
            ->to($email)
            ->subject('Confirmation de réception de votre demande sur Maker Copilot')
            ->html($htmlContentUser);

        // Create admin notification email
        $adminEmail = (new Email())
            ->from($this->noReplyEmail)
            ->to($this->adminEmail)
            ->addTo($this->secondaryEmail)
            ->subject('Message formulaire de contact home page Maker Copilot')
            ->html($htmlContentMe);

        // Send emails
        try {
            $mailer->send($userEmail);
            $mailer->send($adminEmail);

            return new JsonResponse(
                ['status' => 'success', 'message' => 'Emails sent successfully'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            // Log error but don't expose details to client
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Failed to send email'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
