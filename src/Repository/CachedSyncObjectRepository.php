<?php

namespace App\Repository;

use App\Entity\CachedSyncObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CachedSyncObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method CachedSyncObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method CachedSyncObject[]    findAll()
 * @method CachedSyncObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CachedSyncObjectRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CachedSyncObject::class);
    }

    // /**
    //  * @return CachedSyncObject[] Returns an array of CachedSyncObject objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CachedSyncObject
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
