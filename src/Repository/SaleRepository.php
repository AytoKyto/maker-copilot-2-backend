<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Sale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 *
 * @method Sale|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sale|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sale[]    findAll()
 * @method Sale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    /**
     * Find sales by user and date range
     */
    public function findByUserAndDateRange($user, $startDate, $endDate)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Sale[] Returns an array of Sale objects
     */
   /** public function findSalesProductBetweenDate($startDate, $endDate, $userId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.salesProducts', 'sp')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }*/

    /**
     * @return Sale[] Returns an array of Sale objects
     */
    /**public function getTopProductSaleBetweenDate($startDate, $endDate, $userId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select([
                'IDENTITY(sp.product) AS product_id',
                'p.name AS product_name',
                'COUNT(sp.product) AS nb_product',
                'SUM(price.price) AS sumPrice',
                'SUM(price.benefit) AS sumBenefit',
                'SUM(price.commission) AS sumCommission',
                'SUM(price.time) AS sumTime'
            ])
            ->join('s.salesProducts', 'sp')
            ->join('sp.price', 'price')
            ->join('sp.product', 'p')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('product_id')
            ->orderBy('nb_product', 'DESC');

        return $qb->getQuery()->getResult();
    }*/

    /**
     * @return Sale[] Returns an array of Sale objects
     */
    /**public function getTopCanalSaleBetweenDate($startDate, $endDate, $userId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select([
                'IDENTITY(s.canal) AS canal_id',
                'c.name AS canal_name',
                'COUNT(sp.product) AS nb_product',
                'SUM(price.price) AS sumPrice',
                'SUM(price.benefit) AS sumBenefit',
                'SUM(price.commission) AS sumCommission',
                'SUM(price.time) AS sumTime'
            ])
            ->join('s.salesProducts', 'sp')
            ->join('sp.price', 'price')
            ->join('sp.product', 'p')
            ->join('s.canal', 'c')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('canal_id')
            ->orderBy('nb_product', 'DESC');

        return $qb->getQuery()->getResult();
    }*/

    /**public function getTopClientSaleBetweenDate($startDate, $endDate, $userId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select([
                'IDENTITY(sp.client) AS client_id',
                'c.name AS client_name',
                'COUNT(sp.product) AS nb_product',
                'SUM(price.price) AS sumPrice',
                'SUM(price.benefit) AS sumBenefit',
                'SUM(price.commission) AS sumCommission',
                'SUM(price.time) AS sumTime'
            ])
            ->join('s.salesProducts', 'sp')
            ->join('sp.price', 'price')
            ->join('sp.product', 'p')
            ->join('sp.client', 'c')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('client_id')
            ->orderBy('nb_product', 'DESC');

        return $qb->getQuery()->getResult();
    }*/

    /**
     * Find sales with their related products between dates for a user
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $userId
     * @return Sale[]
     */
    public function findSalesProductBetweenDate(\DateTime $startDate, \DateTime $endDate, int $userId): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('sp')
            ->addSelect('p')
            ->addSelect('c')
            ->addSelect('pr')
            ->leftJoin('s.salesProducts', 'sp')
            ->leftJoin('sp.product', 'p')
            ->leftJoin('sp.client', 'c')
            ->leftJoin('sp.price', 'pr')
            ->leftJoin('s.canal', 'ca')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top product sales between dates
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $userId
     * @return array
     */
    public function getTopProductSaleBetweenDate(\DateTime $startDate, \DateTime $endDate, int $userId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.name', 'COUNT(sp.id) as count', 'SUM(s.price) as total')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.product', 'p')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('p.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top sales channels between dates
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $userId
     * @return array
     */
    public function getTopCanalSaleBetweenDate(\DateTime $startDate, \DateTime $endDate, int $userId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.name', 'COUNT(s.id) as count', 'SUM(s.price) as total')
            ->from('App\Entity\Sale', 's')
            ->join('s.canal', 'c')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top client sales between dates
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $userId
     * @return array
     */
    public function getTopClientSaleBetweenDate(\DateTime $startDate, \DateTime $endDate, int $userId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('cl.name', 'COUNT(sp.id) as count', 'SUM(s.price) as total')
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->join('sp.client', 'cl')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->groupBy('cl.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}