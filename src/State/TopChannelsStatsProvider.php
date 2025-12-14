<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TopChannelsStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Top Channels Statistics Provider
 *
 * Provides aggregated statistics for top sales channels.
 * Queries the ViewCanalMonth view for aggregated channel data.
 *
 * @package App\State
 */
class TopChannelsStatsProvider implements ProviderInterface
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
     * Provide top channels statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return array<TopChannelsStats> Array of top channels statistics
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
        $limit = (int) ($request?->query->get('limit', 5));

        // Build DQL query to get channel statistics from Sale entity
        $dql = "
            SELECT
                sc.id as channelId,
                sc.name as channelName,
                SUM(s.nbProduct) as productsCount,
                SUM(s.price) as revenue,
                SUM(s.benefit) as profit
            FROM App\Entity\Sale s
            JOIN s.canal sc
            WHERE 1=1
        ";

        $params = [];

        // Add user filter (required)
        if ($userId) {
            $dql .= " AND s.user = :userId";
            $params['userId'] = $userId;
        }

        // Add month filter
        if ($month) {
            $dql .= " AND MONTH(s.createdAt) = :month";
            $params['month'] = (int) $month;
        }

        // Add year filter
        if ($year) {
            $dql .= " AND YEAR(s.createdAt) = :year";
            $params['year'] = (int) $year;
        }

        // Group by channel
        $dql .= " GROUP BY sc.id, sc.name";

        // Order by revenue (highest first)
        $dql .= " ORDER BY revenue DESC";

        // Execute query
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);
        $query->setMaxResults($limit);

        $results = $query->getResult();

        // Map results to TopChannelsStats objects
        $stats = [];
        foreach ($results as $index => $result) {
            $stats[] = new TopChannelsStats(
                (int) $result['channelId'],
                (string) $result['channelName'],
                (int) $result['productsCount'],
                (float) $result['revenue'],
                (float) $result['profit'],
                $index + 1 // Rank starts at 1
            );
        }

        return $stats;
    }
}
