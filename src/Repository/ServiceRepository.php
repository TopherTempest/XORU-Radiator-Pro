<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Service> */
final class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /** @return list<array{id: int, service_name: string}> */
    public function findOptions(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id AS id', 's.serviceName AS service_name')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
