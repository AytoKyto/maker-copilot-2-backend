<?php

declare(strict_types=1);
// src/Command/TestEmailCommand.php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:test-email',
    description: 'Teste l\'envoi d\'email avec le template de bienvenue',
)]
class TestEmailCommand extends Command
{
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de destination')
            ->setHelp('Cette commande permet de tester l\'envoi d\'email avec le template de bienvenue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $emailAddress = $input->getArgument('email');

        $io->title('Test d\'envoi d\'email');
        $io->text('Destination: ' . $emailAddress);

        try {
            // Vérification de la configuration
            $mailerDsn = $_ENV['MAILER_DSN'] ?? null;
            if (!$mailerDsn || $mailerDsn === 'null://null') {
                $io->error('La configuration email n\'est pas définie dans .env.local');
                $io->note('Créez un fichier .env.local et configurez MAILER_DSN');
                $io->text('Exemple: MAILER_DSN=smtp://user:pass@smtp.gmail.com:587');
                return Command::FAILURE;
            }

            $io->section('Configuration détectée');
            $io->text('MAILER_DSN configuré: ' . preg_replace('/:[^:@]+@/', ':****@', $mailerDsn));

            // Rendu du template
            $io->section('Génération du template');
            $htmlContent = $this->twig->render('email/welcome.html.twig', [
                'email' => $emailAddress
            ]);
            $io->success('Template généré avec succès');

            // Création de l'email
            $io->section('Préparation de l\'email');
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'no-reply@maker-copilot.com')
                ->to($emailAddress)
                ->subject('Test - Bienvenue sur Maker Copilot!')
                ->html($htmlContent);
            
            $io->text('From: ' . ($_ENV['MAILER_FROM'] ?? 'no-reply@maker-copilot.com'));
            $io->text('To: ' . $emailAddress);
            $io->text('Subject: Test - Bienvenue sur Maker Copilot!');

            // Envoi
            $io->section('Envoi de l\'email');
            $this->mailer->send($email);
            
            $io->success('Email envoyé avec succès à ' . $emailAddress);
            $io->note('Vérifiez votre boîte de réception (et les spams)');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi de l\'email');
            $io->text('Message: ' . $e->getMessage());
            $io->text('Fichier: ' . $e->getFile());
            $io->text('Ligne: ' . $e->getLine());
            
            $io->section('Conseils de débogage');
            $io->listing([
                'Vérifiez que MAILER_DSN est correctement configuré dans .env.local',
                'Pour Gmail, utilisez un mot de passe d\'application (pas votre mot de passe habituel)',
                'Vérifiez que le port est correct (587 pour TLS, 465 pour SSL)',
                'Assurez-vous que votre firewall autorise les connexions SMTP sortantes'
            ]);

            return Command::FAILURE;
        }
    }
}