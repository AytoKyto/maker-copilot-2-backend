<?php

declare(strict_types=1);

namespace App\Service\Widget;

use App\ApiResource\WidgetQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Query Builder Service
 *
 * Converts JSON query configurations to secure SQL queries.
 * Provides whitelist-based security for entities, fields, and operators.
 *
 * @package App\Service\Widget
 */
class QueryBuilderService
{
    /**
     * Whitelist of allowed entities
     *
     * @var array<string, string>
     */
    private const ALLOWED_ENTITIES = [
        'Product' => 'App\Entity\Product',
        'Sale' => 'App\Entity\Sale',
        'User' => 'App\Entity\User',
        'Order' => 'App\Entity\Order',
        'SalesChannel' => 'App\Entity\SalesChannel',
        'Client' => 'App\Entity\Client',
        'SalesProduct' => 'App\Entity\SalesProduct',
    ];

    /**
     * Whitelist of allowed fields per entity
     *
     * @var array<string, array<string>>
     */
    private const ALLOWED_FIELDS = [
        'Product' => ['id', 'name', 'imageName', 'isArchived', 'createdAt', 'updatedAt'],
        'Sale' => ['id', 'name', 'price', 'benefit', 'nbProduct', 'ursaf', 'expense', 'commission', 'time', 'createdAt', 'updatedAt'],
        'User' => ['id', 'email', 'firstName', 'lastName', 'roles', 'createdAt', 'updatedAt'],
        'Order' => ['id', 'total', 'status', 'userId', 'createdAt', 'updatedAt'],
        'SalesChannel' => ['id', 'name', 'createdAt', 'updatedAt'],
        'Client' => ['id', 'name', 'createdAt', 'updatedAt'],
        'SalesProduct' => ['id', 'createdAt', 'updatedAt'],
    ];

    /**
     * Allowed operators mapping
     *
     * @var array<string, string>
     */
    private const ALLOWED_OPERATORS = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE',
        'in' => 'IN',
    ];

    /**
     * Maximum allowed limit
     */
    private const MAX_LIMIT = 1000;

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
     * Execute secure query
     *
     * @param WidgetQuery $widgetQuery Query configuration
     * @return array<string, mixed> Query results
     * @throws BadRequestHttpException
     */
    public function executeQuery(WidgetQuery $widgetQuery): array
    {
        // Validate entity
        $this->validateEntity($widgetQuery->from);
        $entityClass = self::ALLOWED_ENTITIES[$widgetQuery->from];

        // Validate SELECT fields
        $this->validateFields($widgetQuery->from, $widgetQuery->select);

        // Build query
        $qb = $this->entityManager->createQueryBuilder();

        // SELECT clause
        $this->buildSelectClause($qb, $widgetQuery->select);

        // FROM clause
        $qb->from($entityClass, 'e');

        // WHERE clause
        if (!empty($widgetQuery->where)) {
            $this->buildWhereClause($qb, $widgetQuery->from, $widgetQuery->where);
        }

        // ORDER BY clause
        if (!empty($widgetQuery->orderBy)) {
            $this->buildOrderByClause($qb, $widgetQuery->from, $widgetQuery->orderBy);
        }

        // LIMIT clause
        if ($widgetQuery->limit !== null) {
            $this->applyLimit($qb, $widgetQuery->limit);
        }

        // OFFSET clause
        if ($widgetQuery->offset !== null) {
            $qb->setFirstResult($widgetQuery->offset);
        }

        // Execute query
        $results = $qb->getQuery()->getResult();

        return [
            'data' => $results,
            'count' => count($results),
            'query' => [
                'entity' => $widgetQuery->from,
                'select' => $widgetQuery->select,
                'filters' => $widgetQuery->where,
            ],
        ];
    }

    /**
     * Validate entity is allowed
     *
     * @param string $entity Entity name
     * @throws BadRequestHttpException
     */
    private function validateEntity(string $entity): void
    {
        if (!isset(self::ALLOWED_ENTITIES[$entity])) {
            throw new BadRequestHttpException(
                "Entity '$entity' is not allowed. Allowed entities: " .
                implode(', ', array_keys(self::ALLOWED_ENTITIES))
            );
        }
    }

    /**
     * Validate fields are allowed for entity
     *
     * @param string $entity Entity name
     * @param array<string> $fields Fields to validate
     * @throws BadRequestHttpException
     */
    private function validateFields(string $entity, array $fields): void
    {
        // Allow wildcard
        if (in_array('*', $fields)) {
            return;
        }

        $allowedFields = self::ALLOWED_FIELDS[$entity] ?? [];

        foreach ($fields as $field) {
            // Allow aggregate functions like COUNT(id)
            if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\(/i', $field)) {
                continue;
            }

            if (!in_array($field, $allowedFields)) {
                throw new BadRequestHttpException(
                    "Field '$field' is not allowed for entity '$entity'. Allowed fields: " .
                    implode(', ', $allowedFields)
                );
            }
        }
    }

    /**
     * Build SELECT clause
     *
     * @param \Doctrine\ORM\QueryBuilder $qb Query builder
     * @param array<string> $fields Fields to select
     */
    private function buildSelectClause($qb, array $fields): void
    {
        if (in_array('*', $fields)) {
            $qb->select('e');
        } else {
            $select = [];
            foreach ($fields as $field) {
                // Handle aggregate functions
                if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\((\w+)\)$/i', $field, $matches)) {
                    $function = strtoupper($matches[1]);
                    $fieldName = $matches[2];
                    $select[] = "{$function}(e.{$fieldName})";
                } else {
                    $select[] = "e.{$field}";
                }
            }
            $qb->select(implode(', ', $select));
        }
    }

    /**
     * Build WHERE clause
     *
     * @param \Doctrine\ORM\QueryBuilder $qb Query builder
     * @param string $entity Entity name
     * @param array<string, mixed> $conditions WHERE conditions
     * @throws BadRequestHttpException
     */
    private function buildWhereClause($qb, string $entity, array $conditions): void
    {
        $paramIndex = 0;

        foreach ($conditions as $key => $value) {
            // Parse field and operator
            [$field, $operator] = $this->parseConditionKey($key);

            // Validate field
            $this->validateField($entity, $field);

            // Get SQL operator
            $sqlOperator = self::ALLOWED_OPERATORS[$operator] ?? '=';

            // Build condition
            $paramName = "param{$paramIndex}";

            if ($operator === 'like') {
                $qb->andWhere("e.{$field} LIKE :{$paramName}")
                   ->setParameter($paramName, "%{$value}%");
            } elseif ($operator === 'in') {
                if (!is_array($value)) {
                    throw new BadRequestHttpException(
                        "Value for IN operator must be an array"
                    );
                }
                $qb->andWhere("e.{$field} IN (:{$paramName})")
                   ->setParameter($paramName, $value);
            } else {
                $qb->andWhere("e.{$field} {$sqlOperator} :{$paramName}")
                   ->setParameter($paramName, $value);
            }

            $paramIndex++;
        }
    }

    /**
     * Parse condition key into field and operator
     *
     * @param string $key Condition key (e.g., "price_gte")
     * @return array{0: string, 1: string} [field, operator]
     */
    private function parseConditionKey(string $key): array
    {
        // Check for operator suffix
        foreach (array_keys(self::ALLOWED_OPERATORS) as $op) {
            if (str_ends_with($key, "_{$op}")) {
                $field = substr($key, 0, -strlen("_{$op}"));
                return [$field, $op];
            }
        }

        // No operator, default to 'eq'
        return [$key, 'eq'];
    }

    /**
     * Validate single field
     *
     * @param string $entity Entity name
     * @param string $field Field name
     * @throws BadRequestHttpException
     */
    private function validateField(string $entity, string $field): void
    {
        $allowedFields = self::ALLOWED_FIELDS[$entity] ?? [];

        if (!in_array($field, $allowedFields)) {
            throw new BadRequestHttpException(
                "Field '{$field}' is not allowed for entity '{$entity}'"
            );
        }
    }

    /**
     * Build ORDER BY clause
     *
     * @param \Doctrine\ORM\QueryBuilder $qb Query builder
     * @param string $entity Entity name
     * @param array<string, string> $orderBy Order configuration
     * @throws BadRequestHttpException
     */
    private function buildOrderByClause($qb, string $entity, array $orderBy): void
    {
        $allowedFields = self::ALLOWED_FIELDS[$entity] ?? [];

        foreach ($orderBy as $field => $direction) {
            // Validate field
            if (!in_array($field, $allowedFields)) {
                throw new BadRequestHttpException(
                    "Field '{$field}' is not allowed in ORDER BY for entity '{$entity}'"
                );
            }

            // Validate direction
            $direction = strtoupper($direction);
            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new BadRequestHttpException(
                    "Invalid sort direction '{$direction}'. Allowed: ASC, DESC"
                );
            }

            $qb->addOrderBy("e.{$field}", $direction);
        }
    }

    /**
     * Apply LIMIT with validation
     *
     * @param \Doctrine\ORM\QueryBuilder $qb Query builder
     * @param int $limit Limit value
     * @throws BadRequestHttpException
     */
    private function applyLimit($qb, int $limit): void
    {
        if ($limit <= 0) {
            throw new BadRequestHttpException('Limit must be greater than 0');
        }

        if ($limit > self::MAX_LIMIT) {
            throw new BadRequestHttpException(
                "Limit cannot exceed " . self::MAX_LIMIT
            );
        }

        $qb->setMaxResults($limit);
    }
}
