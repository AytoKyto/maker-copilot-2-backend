<?php

declare(strict_types=1);

namespace App\Service\Rapport;

use App\Contracts\Strategy\RapportStrategyInterface;

class RapportManager
{
    private $strategies;

    public function __construct($strategies)
    {
        $this->strategies = $strategies;
    }

    public function getStrategy(string $type): RapportStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($type)) {
                return $strategy;
            }
        }

        throw new \DomainException(sprintf('Unable to find a strategy [%s]', $type));
    }

    /**
     * @param RapportStrategyInterface $strategy
     */
    public function addStrategy(RapportStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }
}
