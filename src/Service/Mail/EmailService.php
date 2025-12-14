<?php

declare(strict_types=1);

namespace App\Service\Mail;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{

    private $mailer;
    private $twig;


    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendEmail(string $from, string $to, string $subject, string $template, array $context = []): void
    {

        $htmlTemplate = $this->twig->render($template, $context);


        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlTemplate);

        $this->mailer->send($email);
    }
}
