<?php

declare(strict_types=1);

// src/Controller/FeedBackController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Ayto\NewslaterBundle\Request\FeedbackRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ayto\NewslaterBundle\Service\Mail\EmailService;
use Ayto\NewslaterBundle\Service\Mail\EmailTemplates;

class FeedBackController extends AbstractController
{

   /* private EmailService $emailService;

    public function __construct(
        EmailService $emailService,
    ) {
        $this->emailService = $emailService;
    }*/

    #[Route('/api/feedback', name: 'feedback', methods: ['POST'])]
    public function sendContactEmail(Request $request, ValidatorInterface $validator): JsonResponse
    {

       /* $data = json_decode($request->getContent(), true);

        $feedbackRequest = new FeedbackRequest(
            $data['user_id'] ?? null,
            $data['user_email'] ?? '',
            $data['type'] ?? '',
            $data['message'] ?? ''
        );

        $errors = $validator->validate($feedbackRequest);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['status' => 'error', 'errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user_id = $feedbackRequest->getUserId();
        $user_email = $feedbackRequest->getUserEmail();
        $type = $feedbackRequest->getType();
        $message = $feedbackRequest->getMessage();

        try {
            $this->emailService->sendEmail('no-reply@maker-copilot.com', $user_email, 'Confirmation de réception de votre demande sur Maker Copilot', EmailTemplates::FEEDBACK_EMAIL, []);
            $this->emailService->sendEmail('no-reply@maker-copilot.com', $user_email, 'Message formulaire de contact home page Maker Copilot', EmailTemplates::FEEDBACK_EMAIL_TESTEUR, [
                'user_id' => $user_id,
                'user_email' => $user_email,
                'type' => $type,
                'message' => $message,
            ]);
            return new JsonResponse(['status' => 'success', 'message' => 'Les emails ont été envoyés avec succès.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
       */
        return new JsonResponse(['status' => 'error', 'message' => 'Erreur'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);

    }
}
