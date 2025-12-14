<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Spent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Spent>
 *
 * @method Spent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Spent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Spent[]    findAll()
 * @method Spent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spent::class);
    }

//    /**
//     * @return Spent[] Returns an array of Spent objects
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

//    public function findOneBySomeField($value): ?Spent
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
