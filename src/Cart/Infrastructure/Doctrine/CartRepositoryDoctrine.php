<?php
declare(strict_types=1);

namespace App\Cart\Infrastructure\Doctrine;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class CartRepositoryDoctrine implements CartRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Cart $cart): void
    {
        $this->em->persist($cart);
        $this->em->flush();
    }

    public function findById(string $id): ?Cart
    {
        return $this->em->getRepository(Cart::class)->find($id);
    }

    public function findActiveByUserId(string $userId): ?Cart
    {
        return $this->em->getRepository(Cart::class)->findOneBy([
            'userId' => $userId,
            'status' => Cart::STATUS_ACTIVE,
        ]);
    }
}
