<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SalesProduct;
use App\Entity\Price;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SalesProduct>
 */
class SalesProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalesProduct::class);
    }

    /**
     * Count sales products by price
     */
    public function countByPrice(Price $price): int
    {
        return $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('sp.price = :price')
            ->setParameter('price', $price)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get category statistics for a date range
     */
    public function getCategoryStats($user, $startDate, $endDate): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('cat.name, COUNT(DISTINCT sp.id) as count')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.product', 'p')
            ->join('p.category', 'cat')
            ->where('s.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('cat.id, cat.name')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get profit margins by product
     */
    public function getProfitMarginsByProduct($user, $startDate, $endDate): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.name, SUM(pr.price) as revenue, SUM(pr.benefit) as benefit, 
                     CASE WHEN SUM(pr.price) > 0 THEN (SUM(pr.benefit) / SUM(pr.price) * 100) ELSE 0 END as margin')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.product', 'p')
            ->join('sp.price', 'pr')
            ->where('s.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('p.id, p.name')
            ->orderBy('margin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top clients by date range
     */
    public function getTopClientsByDateRange($user, $startDate, $endDate, $limit = 10): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.id, c.name, COUNT(sp.id) as count, SUM(pr.price) as totalRevenue')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.client', 'c')
            ->join('sp.price', 'pr')
            ->where('s.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('c.id, c.name')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top products by date range
     */
    public function getTopProductsByDateRange($user, $startDate, $endDate, $limit = 10): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.name, COUNT(sp.id) as count, SUM(pr.price) as totalRevenue, SUM(pr.benefit) as totalBenefit')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.product', 'p')
            ->join('sp.price', 'pr')
            ->where('s.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('p.id, p.name')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}