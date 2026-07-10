<?php
declare(strict_types=1);

namespace App\Order\Infrastructure\Doctrine;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class OrderRepositoryDoctrine implements OrderRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Order $order): void
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function findById(string $id): ?Order
    {
        return $this->em->getRepository(Order::class)->find($id);
    }

    public function findByIdForUser(string $id, string $userId): ?Order
    {
        return $this->em->getRepository(Order::class)->findOneBy([
            'id' => $id,
            'userId' => $userId,
        ]);
    }

    public function findByUserId(string $userId, int $limit, int $offset): array
    {
        return $this->em->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->andWhere('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
