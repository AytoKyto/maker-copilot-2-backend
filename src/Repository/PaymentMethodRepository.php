<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PaymentMethod;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentMethod>
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('pm.isDefault', 'DESC')
            ->addOrderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDefaultByUser(User $user): ?PaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.isDefault = :default')
            ->andWhere('pm.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStripeId(string $stripePaymentMethodId): ?PaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.stripePaymentMethodId = :stripeId')
            ->setParameter('stripeId', $stripePaymentMethodId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredCards(): array
    {
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        return $this->createQueryBuilder('pm')
            ->andWhere('pm.type = :type')
            ->andWhere('pm.isActive = :active')
            ->andWhere(
                '(pm.cardExpYear < :currentYear) OR (pm.cardExpYear = :currentYear AND pm.cardExpMonth < :currentMonth)'
            )
            ->setParameter('type', PaymentMethod::TYPE_CARD)
            ->setParameter('active', true)
            ->setParameter('currentYear', $currentYear)
            ->setParameter('currentMonth', $currentMonth)
            ->getQuery()
            ->getResult();
    }

    public function findExpiringSoon(int $monthsThreshold = 2): array
    {
        $now = new \DateTimeImmutable();
        $threshold = $now->modify(sprintf('+%d months', $monthsThreshold));
        $thresholdYear = (int) $threshold->format('Y');
        $thresholdMonth = (int) $threshold->format('n');

        return $this->createQueryBuilder('pm')
            ->andWhere('pm.type = :type')
            ->andWhere('pm.isActive = :active')
            ->andWhere(
                '(pm.cardExpYear < :thresholdYear) OR (pm.cardExpYear = :thresholdYear AND pm.cardExpMonth <= :thresholdMonth)'
            )
            ->andWhere(
                '(pm.cardExpYear > :currentYear) OR (pm.cardExpYear = :currentYear AND pm.cardExpMonth >= :currentMonth)'
            )
            ->setParameter('type', PaymentMethod::TYPE_CARD)
            ->setParameter('active', true)
            ->setParameter('thresholdYear', $thresholdYear)
            ->setParameter('thresholdMonth', $thresholdMonth)
            ->setParameter('currentYear', (int) $now->format('Y'))
            ->setParameter('currentMonth', (int) $now->format('n'))
            ->getQuery()
            ->getResult();
    }
}