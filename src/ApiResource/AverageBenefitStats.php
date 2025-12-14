<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

/**
 * Average Benefit Statistics API Resource
 *
 * Provides average benefit statistics for a given month.
 * Returns current month data and comparison with previous month.
 *
 * API Endpoint:
 * - GET /api/widgets/average-benefit - Get average benefit with trend comparison
 *
 * Query Parameters:
 * - month: Month number (1-12)
 * - year: Year (e.g., 2024)
 * - userId: Filter by user ID
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'AverageBenefitStats',
    operations: [
        new Get(
            uriTemplate: '/widgets/average-benefit',
            provider: 'App\State\AverageBenefitStatsProvider'
        )
    ]
)]
class AverageBenefitStats
{
    /**
     * Total revenue for current month
     *
     * @var float
     */
    public float $revenue;

    /**
     * Total benefit (profit) for current month
     *
     * @var float
     */
    public float $benefitAmount;

    /**
     * Benefit percentage
     *
     * @var float
     */
    public float $benefitPercent;

    /**
     * Previous month's revenue
     *
     * @var float
     */
    public float $previousRevenue;

    /**
     * Previous month's benefit
     *
     * @var float
     */
    public float $previousBenefitAmount;

    /**
     * Previous month's benefit percentage
     *
     * @var float
     */
    public float $previousBenefitPercent;

    /**
     * Constructor
     *
     * @param float $revenue Current month revenue
     * @param float $benefitAmount Current month benefit
     * @param float $benefitPercent Current month benefit percentage
     * @param float $previousRevenue Previous month revenue
     * @param float $previousBenefitAmount Previous month benefit
     * @param float $previousBenefitPercent Previous month benefit percentage
     */
    public function __construct(
        float $revenue,
        float $benefitAmount,
        float $benefitPercent,
        float $previousRevenue,
        float $previousBenefitAmount,
        float $previousBenefitPercent
    ) {
        $this->revenue = $revenue;
        $this->benefitAmount = $benefitAmount;
        $this->benefitPercent = $benefitPercent;
        $this->previousRevenue = $previousRevenue;
        $this->previousBenefitAmount = $previousBenefitAmount;
        $this->previousBenefitPercent = $previousBenefitPercent;
    }
}
