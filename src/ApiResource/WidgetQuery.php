<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

/**
 * Widget Query Resource
 *
 * Provides a secure query builder API for dashboard widgets.
 * Allows frontend to construct flexible queries via JSON structure.
 *
 * API Endpoint:
 * - POST /api/widgets/query
 *
 * Request body examples:
 *
 * Count active products:
 * {
 *   "select": ["id"],
 *   "from": "Product",
 *   "where": { "status": "active" }
 * }
 *
 * Get top 5 products by price:
 * {
 *   "select": ["id", "name", "price"],
 *   "from": "Product",
 *   "orderBy": { "price": "DESC" },
 *   "limit": 5
 * }
 *
 * Search products with LIKE:
 * {
 *   "select": ["*"],
 *   "from": "Product",
 *   "where": { "name_like": "phone" }
 * }
 *
 * Complex query with multiple conditions:
 * {
 *   "select": ["id", "name", "price", "stock"],
 *   "from": "Product",
 *   "where": {
 *     "status": "active",
 *     "price_gte": 10,
 *     "price_lte": 100,
 *     "stock_gt": 0
 *   },
 *   "orderBy": { "price": "DESC", "name": "ASC" },
 *   "limit": 20,
 *   "offset": 0
 * }
 *
 * @package App\ApiResource
 */
#[ApiResource(
    shortName: 'WidgetQuery',
    operations: [
        new Post(
            uriTemplate: '/widgets/query',
            processor: 'App\State\WidgetQueryProcessor'
        )
    ]
)]
class WidgetQuery
{
    /**
     * Fields to select
     *
     * Examples:
     * - ["*"] - All fields
     * - ["id", "name"] - Specific fields
     * - ["COUNT(id)"] - Aggregate functions
     *
     * @var array<string>
     */
    public array $select = ['*'];

    /**
     * Entity to query
     *
     * Allowed values: Product, Sale, User, Order
     *
     * @var string
     */
    public string $from;

    /**
     * WHERE conditions
     *
     * Format: { "field": "value" } or { "field_operator": "value" }
     *
     * Operators:
     * - eq (default): Equal (=)
     * - neq: Not equal (!=)
     * - gt: Greater than (>)
     * - gte: Greater than or equal (>=)
     * - lt: Less than (<)
     * - lte: Less than or equal (<=)
     * - like: LIKE pattern matching
     * - in: IN array of values
     *
     * Examples:
     * - { "status": "active" } - status = 'active'
     * - { "price_gte": 10 } - price >= 10
     * - { "name_like": "phone" } - name LIKE '%phone%'
     * - { "status_in": ["active", "pending"] } - status IN (...)
     *
     * @var array<string, mixed>
     */
    public array $where = [];

    /**
     * ORDER BY configuration
     *
     * Format: { "field": "direction" }
     * Direction: ASC or DESC
     *
     * Examples:
     * - { "price": "DESC" }
     * - { "price": "DESC", "name": "ASC" }
     *
     * @var array<string, string>
     */
    public array $orderBy = [];

    /**
     * Maximum number of results
     *
     * Max allowed: 1000
     *
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * Number of results to skip (for pagination)
     *
     * @var int|null
     */
    public ?int $offset = null;
}
