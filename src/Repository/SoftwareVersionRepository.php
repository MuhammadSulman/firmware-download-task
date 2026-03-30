<?php

namespace App\Repository;

use App\Entity\SoftwareVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoftwareVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoftwareVersion::class);
    }

    public function findBySystemVersionAlt(string $versionAlt): array
    {
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.systemVersionAlt) = LOWER(:version)')
            ->setParameter('version', $versionAlt)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the latest version entry matching a product line prefix (LCI or non-LCI).
     */
    public function findLatestForCategory(bool $isLCI): ?SoftwareVersion
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.latest = true');

        if ($isLCI) {
            $qb->andWhere('s.name LIKE :prefix')
                ->setParameter('prefix', 'LCI%');
        } else {
            $qb->andWhere('s.name NOT LIKE :prefix')
                ->setParameter('prefix', 'LCI%');
        }

        return $qb->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Clear the latest flag for all versions in a given product line.
     */
    public function clearLatestForProductLine(string $productName): int
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.latest', 'false')
            ->where('s.name = :name')
            ->andWhere('s.latest = true')
            ->setParameter('name', $productName)
            ->getQuery()
            ->execute();
    }
}
