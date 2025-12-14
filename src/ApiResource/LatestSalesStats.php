<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

/**
 * Latest Sales Statistics API Resource
 *
 * Provides the latest sales with channel information.
 * Returns sales ordered by creation date (most recent first).
 *
 * API Endpoint:
 * - GET /api/widgets/latest-sales - Get latest sales with channel names
 *
 * Query Parameters:
 * - userId: Filter by user ID
 * - limit: Number of results (default: 4)
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'LatestSalesStats',
    operations: [
        new GetCollection(
            uriTemplate: '/widgets/latest-sales',
            provider: 'App\State\LatestSalesStatsProvider'
        )
    ]
)]
class LatestSalesStats
{
    /**
     * Sale ID
     *
     * @var int
     */
    public int $id;

    /**
     * Sale name
     *
     * @var string
     */
    public string $name;

    /**
     * Number of products in this sale
     *
     * @var int
     */
    public int $nbProduct;

    /**
     * Sales channel name
     *
     * @var string
     */
    public string $canalName;

    /**
     * Sale price (revenue)
     *
     * @var float
     */
    public float $price;

    /**
     * Sale benefit (profit)
     *
     * @var float
     */
    public float $benefit;

    /**
     * Sale creation date
     *
     * @var string ISO 8601 date format
     */
    public string $createdAt;

    /**
     * Constructor
     *
     * @param int $id Sale ID
     * @param string $name Sale name
     * @param int $nbProduct Number of products
     * @param string $canalName Channel name
     * @param float $price Sale price
     * @param float $benefit Sale benefit
     * @param string $createdAt Creation date
     */
    public function __construct(
        int $id,
        string $name,
        int $nbProduct,
        string $canalName,
        float $price,
        float $benefit,
        string $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->nbProduct = $nbProduct;
        $this->canalName = $canalName;
        $this->price = $price;
        $this->benefit = $benefit;
        $this->createdAt = $createdAt;
    }
}
