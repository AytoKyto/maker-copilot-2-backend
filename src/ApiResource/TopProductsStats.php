<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

/**
 * Top Products Statistics API Resource
 *
 * Provides aggregated statistics for top-selling products.
 * Returns products ranked by number of sales.
 *
 * API Endpoint:
 * - GET /api/widgets/top-products - Get top products with sales count
 *
 * Query Parameters:
 * - month: Filter by month (1-12)
 * - year: Filter by year (e.g., 2024)
 * - userId: Filter by user ID
 * - limit: Number of results (default: 6)
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'TopProductsStats',
    operations: [
        new GetCollection(
            uriTemplate: '/widgets/top-products',
            provider: 'App\State\TopProductsStatsProvider'
        )
    ]
)]
class TopProductsStats
{
    /**
     * Product ID
     *
     * @var int
     */
    public int $productId;

    /**
     * Product name
     *
     * @var string
     */
    public string $productName;

    /**
     * Number of sales for this product
     *
     * @var int
     */
    public int $salesCount;

    /**
     * Rank position (1 = best seller)
     *
     * @var int
     */
    public int $rank;

    /**
     * Constructor
     *
     * @param int $productId Product ID
     * @param string $productName Product name
     * @param int $salesCount Number of sales
     * @param int $rank Rank position
     */
    public function __construct(
        int $productId,
        string $productName,
        int $salesCount,
        int $rank
    ) {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->salesCount = $salesCount;
        $this->rank = $rank;
    }
}
