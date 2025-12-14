<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findActiveByUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.stripeSubscriptionId = :stripeId')
            ->setParameter('stripeId', $stripeSubscriptionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiringSoon(int $daysThreshold = 7): array
    {
        $threshold = new \DateTimeImmutable(sprintf('+%d days', $daysThreshold));

        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.currentPeriodEnd <= :threshold')
            ->andWhere('s.currentPeriodEnd > :now')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('threshold', $threshold)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findCanceledSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_CANCELED)
            ->orderBy('s.canceledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSubscriptionsByPlan(string $planSlug): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.plan', 'p')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $planSlug)
            ->getQuery()
            ->getResult();
    }

    public function countActiveSubscriptions(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMonthlyRevenue(\DateTimeInterface $month = null): float
    {
        if (!$month) {
            $month = new \DateTimeImmutable();
        }

        $startOfMonth = $month->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = $month->modify('last day of this month')->setTime(23, 59, 59);

        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.amount)')
            ->andWhere('s.status = :status')
            ->andWhere('s.currentPeriodStart >= :start')
            ->andWhere('s.currentPeriodStart <= :end')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?: 0.0;
    }
}