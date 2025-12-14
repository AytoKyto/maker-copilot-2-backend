<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\LatestSalesStats;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Latest Sales Statistics Provider
 *
 * Provides the latest sales with channel information.
 * Queries Sale entity joined with SalesChannel.
 *
 * @package App\State
 */
class LatestSalesStatsProvider implements ProviderInterface
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
     * Provide latest sales statistics
     *
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return array<LatestSalesStats> Array of latest sales statistics
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array {
        // Get query parameters from request
        $request = $context['request'] ?? null;
        $userId = $request?->query->get('userId');
        $limit = (int) ($request?->query->get('limit', 4));

        // Build DQL query to get latest sales with channel information
        $dql = "
            SELECT
                s.id as id,
                s.name as name,
                s.nbProduct as nbProduct,
                sc.name as canalName,
                s.price as price,
                s.benefit as benefit,
                s.createdAt as createdAt
            FROM App\Entity\Sale s
            LEFT JOIN s.canal sc
            WHERE 1=1
        ";

        $params = [];

        // Add user filter
        if ($userId) {
            $dql .= " AND s.user = :userId";
            $params['userId'] = $userId;
        }

        // Order by creation date (most recent first)
        $dql .= " ORDER BY s.createdAt DESC";

        // Execute query
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);
        $query->setMaxResults($limit);

        $results = $query->getResult();

        // Map results to LatestSalesStats objects
        $stats = [];
        foreach ($results as $result) {
            $stats[] = new LatestSalesStats(
                (int) $result['id'],
                (string) $result['name'],
                (int) $result['nbProduct'],
                (string) ($result['canalName'] ?? 'N/A'),
                (float) $result['price'],
                (float) $result['benefit'],
                $result['createdAt']->format('c') // ISO 8601 format
            );
        }

        return $stats;
    }
}
