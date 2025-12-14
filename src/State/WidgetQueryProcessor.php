<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\WidgetQuery;
use App\Service\Widget\QueryBuilderService;

/**
 * Widget Query Processor
 *
 * Processes widget query requests and returns results.
 *
 * @package App\State
 */
class WidgetQueryProcessor implements ProcessorInterface
{
    /**
     * Constructor
     *
     * @param QueryBuilderService $queryBuilderService Query builder service
     */
    public function __construct(
        private readonly QueryBuilderService $queryBuilderService
    ) {
    }

    /**
     * Process widget query request
     *
     * @param WidgetQuery $data Widget query configuration
     * @param Operation $operation API Platform operation
     * @param array<string, mixed> $uriVariables URI variables
     * @param array<string, mixed> $context Request context
     *
     * @return array<string, mixed> Query results
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array {
        return $this->queryBuilderService->executeQuery($data);
    }
}
