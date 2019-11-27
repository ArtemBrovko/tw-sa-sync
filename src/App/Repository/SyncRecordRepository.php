<?php

namespace App\Repository;

use App\Entity\SyncRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SyncRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method SyncRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method SyncRecord[]    findAll()
 * @method SyncRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SyncRecordRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SyncRecord::class);
    }

    // /**
    //  * @return SyncRecord[] Returns an array of SyncRecord objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SyncRecord
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
