<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Repository;

use App\Inventory\Infrastructure\Doctrine\Entity\StockLog;
use Doctrine\ORM\EntityManagerInterface;

class StockLogDoctrineRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(StockLog $log): void
    {
        $this->em->persist($log);
        $this->em->flush();
    }
}
