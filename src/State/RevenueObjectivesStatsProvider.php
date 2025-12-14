<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\RevenueObjectivesStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Revenue Objectives Statistics Provider
 *
 * Provides revenue statistics with category breakdown.
 * Queries Sale entity grouped by product category.
 *
 * @package App\State
 */
class RevenueObjectivesStatsProvider implements ProviderInterface
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
     * Provide revenue objectives statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return RevenueObjectivesStats Revenue objectives statistics object
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): RevenueObjectivesStats {
        // Get query parameters from request
        $request = $context['request'] ?? null;
        $month = $request?->query->get('month');
        $year = $request?->query->get('year');
        $userId = $request?->query->get('userId');

        // Build DQL query to get revenue by category
        // Join Sale -> SalesProduct -> Product -> Category
        $dql = "
            SELECT
                c.id as categoryId,
                c.name as categoryName,
                SUM(s.price) as revenue,
                SUM(s.benefit) as profit
            FROM App\Entity\Sale s
            JOIN s.salesProducts sp
            JOIN sp.product p
            LEFT JOIN p.category c
            WHERE 1=1
        ";

        $params = [];

        // Add user filter
        if ($userId) {
            $dql .= " AND s.user = :userId";
            $params['userId'] = $userId;
        }

        // Add month filter
        if ($month) {
            $dql .= " AND s.month = :month";
            $params['month'] = (int) $month;
        }

        // Add year filter
        if ($year) {
            $dql .= " AND s.year = :year";
            $params['year'] = (int) $year;
        }

        // Group by category
        $dql .= " GROUP BY c.id, c.name";
        $dql .= " ORDER BY revenue DESC";

        // Execute query
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);

        $results = $query->getResult();

        // Calculate total revenue
        $total = 0.0;
        foreach ($results as $result) {
            $total += (float) $result['revenue'];
        }

        // Build categories array with percentages
        $categories = [];
        foreach ($results as $result) {
            $revenue = (float) $result['revenue'];
            $profit = (float) $result['profit'];
            $percent = $total > 0 ? ($revenue / $total) * 100 : 0.0;

            $categories[] = [
                'categoryId' => (int) $result['categoryId'],
                'categoryName' => (string) ($result['categoryName'] ?? 'Sans catégorie'),
                'amount' => $revenue,
                'percent' => $percent,
                'profitAmount' => $profit
            ];
        }

        return new RevenueObjectivesStats($total, $categories);
    }
}
