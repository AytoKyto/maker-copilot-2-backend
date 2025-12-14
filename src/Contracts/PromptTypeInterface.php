<?php

declare(strict_types=1);

namespace App\Contracts;

interface PromptTypeInterface
{
    public const SALE_ANALYSIS = 'Sales Analysis';
    public const PRODUCT_PERF = 'Product Performance';
    public const CUSTOM_INSIGHTS = 'Customer Insights';
    public const CHANNEL_ANALYSIS = 'Channel Analysis';
    public const PROFITABILITY_STRATEGY = 'Profitability Strategy';
    public const EMAIL_RAPPORT = 'Rapport Email';
}
