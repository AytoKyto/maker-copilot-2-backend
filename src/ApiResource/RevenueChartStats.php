<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

/**
 * Revenue Chart Statistics API Resource
 *
 * Provides monthly revenue and profit data for chart visualization.
 * Returns aggregated data grouped by month.
 *
 * API Endpoint:
 * - GET /api/widgets/revenue-chart - Get monthly revenue and profit statistics
 *
 * Query Parameters:
 * - year: Filter by year (e.g., 2024)
 * - userId: Filter by user ID
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'RevenueChartStats',
    operations: [
        new GetCollection(
            uriTemplate: '/widgets/revenue-chart',
            provider: 'App\State\RevenueChartStatsProvider'
        )
    ]
)]
class RevenueChartStats
{
    /**
     * Month number (1-12)
     *
     * @var int
     */
    public int $month;

    /**
     * Total revenue for the month
     *
     * @var float
     */
    public float $revenue;

    /**
     * Total profit for the month
     *
     * @var float
     */
    public float $profit;

    /**
     * Constructor
     *
     * @param int $month Month number (1-12)
     * @param float $revenue Total revenue
     * @param float $profit Total profit
     */
    public function __construct(
        int $month,
        float $revenue,
        float $profit
    ) {
        $this->month = $month;
        $this->revenue = $revenue;
        $this->profit = $profit;
    }
}
