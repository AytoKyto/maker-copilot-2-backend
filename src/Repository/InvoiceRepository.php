<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStripeId(string $stripeInvoiceId): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.stripeInvoiceId = :stripeId')
            ->setParameter('stripeId', $stripeInvoiceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaidInvoicesByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->orderBy('i.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueInvoices(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status != :paidStatus')
            ->andWhere('i.dueDate < :now')
            ->setParameter('paidStatus', Invoice::STATUS_PAID)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUnpaidInvoicesByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->andWhere('i.status != :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenueByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.total)')
            ->andWhere('i.status = :status')
            ->andWhere('i.paidAt >= :start')
            ->andWhere('i.paidAt <= :end')
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?: 0.0;
    }

    public function getMonthlyRevenue(\DateTimeInterface $month = null): float
    {
        if (!$month) {
            $month = new \DateTimeImmutable();
        }

        $startOfMonth = $month->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = $month->modify('last day of this month')->setTime(23, 59, 59);

        return $this->getTotalRevenueByPeriod($startOfMonth, $endOfMonth);
    }

    public function countInvoicesByStatus(string $status): int
    {
        return $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUserPaginated(User $user, int $page = 1, int $limit = 20, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($status) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByUser(User $user, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getUserInvoiceStats(User $user): array
    {
        $totalPaid = $this->createQueryBuilder('i')
            ->select('SUM(i.amountPaid)')
            ->andWhere('i.user = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        $totalUnpaid = $this->createQueryBuilder('i')
            ->select('SUM(i.amountDue)')
            ->andWhere('i.user = :user')
            ->andWhere('i.status != :status')
            ->setParameter('user', $user)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        $countTotal = $this->countByUser($user);
        $countPaid = $this->countByUser($user, Invoice::STATUS_PAID);

        return [
            'total_paid' => (float) ($totalPaid ?: 0),
            'total_unpaid' => (float) ($totalUnpaid ?: 0),
            'count_total' => $countTotal,
            'count_paid' => $countPaid,
            'count_unpaid' => $countTotal - $countPaid
        ];
    }
}