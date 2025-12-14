<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TopProductsStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Top Products Statistics Provider
 *
 * Provides aggregated statistics for top-selling products.
 * Executes custom DQL query to count sales per product.
 *
 * @package App\State
 */
class TopProductsStatsProvider implements ProviderInterface
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
     * Provide top products statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return array<TopProductsStats> Array of top products statistics
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array {
        // Get query parameters from request
        $request = $context['request'] ?? null;
        $month = $request?->query->get('month');
        $year = $request?->query->get('year');
        $userId = $request?->query->get('userId');
        $limit = (int) ($request?->query->get('limit', 6));

        // Build DQL query to get product sales count
        $dql = "
            SELECT
                p.id as productId,
                p.name as productName,
                COUNT(sp.id) as salesCount
            FROM App\Entity\SalesProduct sp
            JOIN sp.product p
            JOIN sp.sale s
            WHERE 1=1
        ";

        $params = [];

        // Add user filter
        if ($userId) {
            $dql .= " AND s.user = :userId";
            $params['userId'] = $userId;
        }

        // Add month/year filter
        if ($month && $year) {
            $dql .= " AND MONTH(s.createdAt) = :month AND YEAR(s.createdAt) = :year";
            $params['month'] = (int) $month;
            $params['year'] = (int) $year;
        } elseif ($year) {
            // Year only filter
            $dql .= " AND YEAR(s.createdAt) = :year";
            $params['year'] = (int) $year;
        }

        // Group and order
        $dql .= " GROUP BY p.id, p.name";
        $dql .= " ORDER BY salesCount DESC";

        // Execute query
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);
        $query->setMaxResults($limit);

        $results = $query->getResult();

        // Map results to TopProductsStats objects
        $stats = [];
        foreach ($results as $index => $result) {
            $stats[] = new TopProductsStats(
                (int) $result['productId'],
                (string) $result['productName'],
                (int) $result['salesCount'],
                $index + 1 // Rank starts at 1
            );
        }

        return $stats;
    }
}
