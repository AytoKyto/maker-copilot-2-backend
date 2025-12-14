<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\RevenueChartStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Revenue Chart Statistics Provider
 *
 * Provides monthly revenue and profit statistics for chart visualization.
 * Groups sales by month and aggregates revenue and profit.
 *
 * @package App\State
 */
class RevenueChartStatsProvider implements ProviderInterface
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
     * Provide revenue chart statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return array<RevenueChartStats> Array of monthly revenue statistics
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array {
        // Get query parameters from request
        $request = $context['request'] ?? null;
        $year = $request?->query->get('year');
        $userId = $request?->query->get('userId');

        // Build DQL query to get monthly revenue and profit
        $dql = "
            SELECT
                MONTH(s.createdAt) as month,
                SUM(s.price) as revenue,
                SUM(s.benefit) as profit
            FROM App\Entity\Sale s
            WHERE 1=1
        ";

        $params = [];

        // Add user filter
        if ($userId) {
            $dql .= " AND s.user = :userId";
            $params['userId'] = $userId;
        }

        // Add year filter
        if ($year) {
            $dql .= " AND YEAR(s.createdAt) = :year";
            $params['year'] = (int) $year;
        }

        // Group by month
        $dql .= " GROUP BY MONTH(s.createdAt)";
        $dql .= " ORDER BY MONTH(s.createdAt) ASC";

        // Execute query
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);

        $results = $query->getResult();

        // Create a map of months to data
        $monthData = [];
        foreach ($results as $result) {
            $monthData[(int) $result['month']] = [
                'revenue' => (float) $result['revenue'],
                'profit' => (float) $result['profit']
            ];
        }

        // Fill in all 12 months (with 0 for missing months)
        $stats = [];
        for ($month = 1; $month <= 12; $month++) {
            $stats[] = new RevenueChartStats(
                $month,
                $monthData[$month]['revenue'] ?? 0.0,
                $monthData[$month]['profit'] ?? 0.0
            );
        }

        return $stats;
    }
}
