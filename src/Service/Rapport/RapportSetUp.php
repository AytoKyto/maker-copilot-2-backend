<?php

declare(strict_types=1);

namespace App\Service\Rapport;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class RapportSetUp
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function extractAndValidateDates(Request $request): array
    {
        $data = $request->toArray();
        $date1 = $data['date1'] ?? null;
        $date2 = $data['date2'] ?? null;

        if (!$date1 || !$date2) {
            throw new \InvalidArgumentException('Date parameters are missing.');
        }

        try {
            $startDate = new \DateTime($date1 . ' 00:00:00');
            $endDate = new \DateTime($date2 . ' 23:59:59');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format. Please use YYYY-MM-DD.');
        }

        return [$startDate, $endDate];
    }

    public function getAuthenticatedUserId(): int
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new \RuntimeException('User not authenticated');
        }

        return $user->getId();
    }
}
