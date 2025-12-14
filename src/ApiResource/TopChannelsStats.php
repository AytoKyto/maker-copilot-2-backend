<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

/**
 * Top Channels Statistics API Resource
 *
 * Provides aggregated statistics for top-selling sales channels.
 * Returns channels ranked by revenue.
 *
 * API Endpoint:
 * - GET /api/widgets/top-channels - Get top channels with sales count, revenue, profit
 *
 * Query Parameters:
 * - month: Filter by month (1-12)
 * - year: Filter by year (e.g., 2024)
 * - userId: Filter by user ID
 * - limit: Number of results (default: 5)
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'TopChannelsStats',
    operations: [
        new GetCollection(
            uriTemplate: '/widgets/top-channels',
            provider: 'App\State\TopChannelsStatsProvider'
        )
    ]
)]
class TopChannelsStats
{
    /**
     * Channel ID
     *
     * @var int
     */
    public int $channelId;

    /**
     * Channel name
     *
     * @var string
     */
    public string $channelName;

    /**
     * Number of products sold through this channel
     *
     * @var int
     */
    public int $productsCount;

    /**
     * Total revenue for this channel
     *
     * @var float
     */
    public float $revenue;

    /**
     * Total profit for this channel
     *
     * @var float
     */
    public float $profit;

    /**
     * Rank position (1 = highest revenue)
     *
     * @var int
     */
    public int $rank;

    /**
     * Constructor
     *
     * @param int $channelId Channel ID
     * @param string $channelName Channel name
     * @param int $productsCount Number of products sold
     * @param float $revenue Total revenue
     * @param float $profit Total profit
     * @param int $rank Rank position
     */
    public function __construct(
        int $channelId,
        string $channelName,
        int $productsCount,
        float $revenue,
        float $profit,
        int $rank
    ) {
        $this->channelId = $channelId;
        $this->channelName = $channelName;
        $this->productsCount = $productsCount;
        $this->revenue = $revenue;
        $this->profit = $profit;
        $this->rank = $rank;
    }
}
