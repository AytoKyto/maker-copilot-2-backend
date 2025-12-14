<?php

declare(strict_types=1);

namespace App\Contracts\Strategy;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

interface RapportStrategyInterface
{
    public function supports(string $type): bool;

    public function getData(
        Request             $request,
        SerializerInterface $serializer,
        Security            $security
    ): JsonResponse;

    public function getPrompt(): string;
}
