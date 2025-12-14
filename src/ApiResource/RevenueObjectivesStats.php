<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

/**
 * Revenue Objectives Statistics API Resource
 *
 * Provides revenue breakdown by category for a given month.
 * Returns total revenue and category-level details.
 *
 * API Endpoint:
 * - GET /api/widgets/revenue-objectives - Get revenue with category breakdown
 *
 * Query Parameters:
 * - month: Month number (1-12)
 * - year: Year (e.g., 2024)
 * - userId: Filter by user ID
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'RevenueObjectivesStats',
    operations: [
        new Get(
            uriTemplate: '/widgets/revenue-objectives',
            provider: 'App\State\RevenueObjectivesStatsProvider'
        )
    ]
)]
class RevenueObjectivesStats
{
    /**
     * Total revenue for the month
     *
     * @var float
     */
    public float $total;

    /**
     * Categories breakdown
     *
     * @var array<array{categoryId: int, categoryName: string, amount: float, percent: float, profitAmount: float}>
     */
    public array $categories;

    /**
     * Constructor
     *
     * @param float $total Total revenue
     * @param array<array{categoryId: int, categoryName: string, amount: float, percent: float, profitAmount: float}> $categories Categories breakdown
     */
    public function __construct(
        float $total,
        array $categories
    ) {
        $this->total = $total;
        $this->categories = $categories;
    }
}
