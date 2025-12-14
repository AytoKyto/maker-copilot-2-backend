<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\AverageBenefitStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Average Benefit Statistics Provider
 *
 * Provides average benefit statistics using ViewBenefitMonth view.
 * Compares current month with previous month for trend analysis.
 *
 * @package App\State
 */
class AverageBenefitStatsProvider implements ProviderInterface
{
    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Provide average benefit statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return AverageBenefitStats Average benefit statistics object
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): AverageBenefitStats {
        // Get query parameters from request
        $request = $context['request'] ?? null;
        $month = $request?->query->get('month');
        $year = $request?->query->get('year');
        $userId = $request?->query->get('userId');

        // Get current month data
        $currentData = $this->getMonthData($userId, $month, $year);

        // Calculate previous month
        $previousMonth = (int) $month - 1;
        $previousYear = (int) $year;
        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear--;
        }

        // Get previous month data
        $previousData = $this->getMonthData($userId, $previousMonth, $previousYear);

        return new AverageBenefitStats(
            $currentData['revenue'],
            $currentData['benefit'],
            $currentData['benefitPercent'],
            $previousData['revenue'],
            $previousData['benefit'],
            $previousData['benefitPercent']
        );
    }

    /**
     * Get benefit data for a specific month from Sale entity
     *
     * @param mixed $userId User ID
     * @param int $month Month number (1-12)
     * @param int $year Year
     *
     * @return array<string, float> Array with revenue, benefit, and benefitPercent
     */
    private function getMonthData($userId, int $month, int $year): array
    {
        $dql = "
            SELECT
                SUM(s.price) as revenue,
                SUM(s.benefit) as benefit
            FROM App\Entity\Sale s
            WHERE s.user = :userId
            AND MONTH(s.createdAt) = :month
            AND YEAR(s.createdAt) = :year
        ";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameters([
            'userId' => $userId,
            'month' => $month,
            'year' => $year
        ]);

        $result = $query->getOneOrNullResult();

        if (!$result || $result['revenue'] === null) {
            return [
                'revenue' => 0.0,
                'benefit' => 0.0,
                'benefitPercent' => 0.0
            ];
        }

        $revenue = (float) $result['revenue'];
        $benefit = (float) $result['benefit'];
        $benefitPercent = $revenue > 0 ? ($benefit / $revenue) * 100 : 0.0;

        return [
            'revenue' => $revenue,
            'benefit' => $benefit,
            'benefitPercent' => $benefitPercent
        ];
    }
}
