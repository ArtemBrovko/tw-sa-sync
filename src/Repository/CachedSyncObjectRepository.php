<?php

namespace App\Repository;

use App\Entity\CachedSyncObject;
use App\Entity\SyncRecord;
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

    /**
     * @param $syncRecord SyncRecord
     *
     * @return string[] Returns an array of already synced
     */
    public function findBySyncRecord(SyncRecord $syncRecord)
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.transferWiseId')
            ->innerJoin('c.job', 'job')
            ->andWhere('job.syncRecord = :val')
            ->setParameter('val', $syncRecord)
            ->getQuery()
            ->getScalarResult()
        ;

        return array_column($rows, 'transferWiseId');
    }

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
