<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SalesProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SalesProduct>
 *
 * @method SalesProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method SalesProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method SalesProduct[]    findAll()
 * @method SalesProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SaleProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalesProduct::class);
    }

    /**
     * @return SalesProduct[] Returns an array of Sale objects
     */
    public function findSalesProductBetweenDate($startDate, $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
