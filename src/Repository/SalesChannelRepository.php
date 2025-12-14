<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SalesChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SalesChannel>
 *
 * @method SalesChannel|null find($id, $lockMode = null, $lockVersion = null)
 * @method SalesChannel|null findOneBy(array $criteria, array $orderBy = null)
 * @method SalesChannel[]    findAll()
 * @method SalesChannel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SalesChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalesChannel::class);
    }

//    /**
//     * @return SalesChannel[] Returns an array of SalesChannel objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SalesChannel
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
