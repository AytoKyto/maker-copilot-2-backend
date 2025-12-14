<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-stripe-plans',
    description: 'Create the Stripe subscription plans in database',
)]
class CreateStriplePlansCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Clear existing plans
        $existingPlans = $this->entityManager->getRepository(Plan::class)->findAll();
        foreach ($existingPlans as $plan) {
            $this->entityManager->remove($plan);
        }

        // Plan Starter (Free)
        $starterPlan = new Plan();
        $starterPlan->setName('Starter');
        $starterPlan->setSlug('starter');
        $starterPlan->setDescription('Plan gratuit pour découvrir l\'application');
        $starterPlan->setMonthlyPrice(0);
        $starterPlan->setYearlyPrice(0);
        $starterPlan->setMaxProducts(3);
        $starterPlan->setHasDetailedReports(false);
        $starterPlan->setStripeMonthlyPriceId(null);
        $starterPlan->setStripeYearlyPriceId(null);
        $starterPlan->setRoles(['ROLE_USER']);
        $starterPlan->setFeatures([
            'Jusqu\'à 3 produits',
            'Tableau de bord basique',
            'Support communautaire'
        ]);
        $starterPlan->setIsActive(true);
        $starterPlan->setIsPopular(false);

        $this->entityManager->persist($starterPlan);

        // Plan Pro
        $proPlan = new Plan();
        $proPlan->setName('Pro');
        $proPlan->setSlug('pro');
        $proPlan->setDescription('Plan professionnel pour les entrepreneurs');
        $proPlan->setMonthlyPrice(5.00);
        $proPlan->setYearlyPrice(55.00);
        $proPlan->setMaxProducts(50);
        $proPlan->setHasDetailedReports(true);
        // À remplacer par vos vrais Price IDs Stripe
        $proPlan->setStripeMonthlyPriceId('price_1234567890_monthly_pro');
        $proPlan->setStripeYearlyPriceId('price_1234567890_yearly_pro');
        $proPlan->setRoles(['ROLE_USER', 'ROLE_PRO']);
        $proPlan->setFeatures([
            'Jusqu\'à 50 produits',
            'Rapports détaillés',
            'Support prioritaire',
            '14 jours d\'essai gratuit'
        ]);
        $proPlan->setIsActive(true);
        $proPlan->setIsPopular(true); // Plan le plus populaire

        $this->entityManager->persist($proPlan);

        // Plan Unlimited
        $unlimitedPlan = new Plan();
        $unlimitedPlan->setName('Unlimited');
        $unlimitedPlan->setSlug('unlimited');
        $unlimitedPlan->setDescription('Plan illimité pour les grandes entreprises');
        $unlimitedPlan->setMonthlyPrice(10.00);
        $unlimitedPlan->setYearlyPrice(100.00);
        $unlimitedPlan->setMaxProducts(-1); // Illimité
        $unlimitedPlan->setHasDetailedReports(true);
        // À remplacer par vos vrais Price IDs Stripe
        $unlimitedPlan->setStripeMonthlyPriceId('price_1234567890_monthly_unlimited');
        $unlimitedPlan->setStripeYearlyPriceId('price_1234567890_yearly_unlimited');
        $unlimitedPlan->setRoles(['ROLE_USER', 'ROLE_PRO', 'ROLE_UNLIMITED']);
        $unlimitedPlan->setFeatures([
            'Produits illimités',
            'Rapports avancés',
            'Support premium',
            'Accès API avancé',
            'Exports illimités',
            '14 jours d\'essai gratuit'
        ]);
        $unlimitedPlan->setIsActive(true);
        $unlimitedPlan->setIsPopular(false);

        $this->entityManager->persist($unlimitedPlan);

        $this->entityManager->flush();

        $io->success('Plans Stripe créés avec succès !');
        $io->table(
            ['Nom', 'Prix Mensuel', 'Prix Annuel', 'Produits Max', 'Rapports'],
            [
                ['Starter', '0€', '0€', '3', 'Non'],
                ['Pro', '5€', '55€', '50', 'Oui'],
                ['Unlimited', '10€', '100€', 'Illimité', 'Oui'],
            ]
        );

        $io->note('N\'oubliez pas de mettre à jour les Price IDs Stripe dans les entités Plan !');

        return Command::SUCCESS;
    }
}