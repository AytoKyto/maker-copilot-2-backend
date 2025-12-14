<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

/**
 * Custom Price Range Filter
 * 
 * Filters products based on price range (min and max).
 * Searches through all prices associated with a product.
 * 
 * @package App\Filter
 */
final class PriceRangeFilter extends AbstractFilter
{
    /**
     * Get filter description for API documentation
     * 
     * @param string $resourceClass Resource class name
     * 
     * @return array<string, array<string, mixed>> Filter description
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property => $strategy) {
            $description[sprintf('%s[min]', $property)] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_FLOAT,
                'required' => false,
                'description' => 'Minimum price filter',
                'openapi' => [
                    'example' => 10.00,
                    'description' => 'Filter products with price greater than or equal to this value',
                ],
            ];

            $description[sprintf('%s[max]', $property)] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_FLOAT,
                'required' => false,
                'description' => 'Maximum price filter',
                'openapi' => [
                    'example' => 100.00,
                    'description' => 'Filter products with price less than or equal to this value',
                ],
            ];
        }

        return $description;
    }

    /**
     * Apply price range filter to query
     * 
     * @param QueryBuilder $queryBuilder Query builder instance
     * @param QueryNameGeneratorInterface $queryNameGenerator Query name generator
     * @param string $resourceClass Resource class name
     * @param Operation|null $operation Current operation
     * @param array<string, mixed> $context Request context
     * 
     * @return void
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // Validate that the property is configured
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        // Validate value structure
        if (!is_array($value) || (!isset($value['min']) && !isset($value['max']))) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $priceAlias = $queryNameGenerator->generateJoinAlias('prices');

        // Join with prices table if not already joined
        $joins = $queryBuilder->getDQLPart('join');
        $priceJoined = false;
        
        if (isset($joins[$alias])) {
            foreach ($joins[$alias] as $join) {
                if ($join->getAlias() === $priceAlias) {
                    $priceJoined = true;
                    break;
                }
            }
        }

        if (!$priceJoined) {
            $queryBuilder
                ->leftJoin(sprintf('%s.prices', $alias), $priceAlias);
        }

        // Apply min price filter
        if (isset($value['min']) && is_numeric($value['min'])) {
            $minParam = $queryNameGenerator->generateParameterName('minPrice');
            $queryBuilder
                ->andWhere(sprintf('%s.price >= :%s', $priceAlias, $minParam))
                ->setParameter($minParam, (float) $value['min']);
        }

        // Apply max price filter
        if (isset($value['max']) && is_numeric($value['max'])) {
            $maxParam = $queryNameGenerator->generateParameterName('maxPrice');
            $queryBuilder
                ->andWhere(sprintf('%s.price <= :%s', $priceAlias, $maxParam))
                ->setParameter($maxParam, (float) $value['max']);
        }

        // Ensure we get distinct products
        $queryBuilder->distinct();
    }
}