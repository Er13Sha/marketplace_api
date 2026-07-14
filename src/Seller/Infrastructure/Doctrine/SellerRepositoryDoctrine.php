<?php
declare(strict_types=1);

namespace App\Seller\Infrastructure\Doctrine;

use App\Seller\Domain\Entity\Seller;
use App\Seller\Domain\Repository\SellerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class SellerRepositoryDoctrine implements SellerRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Seller $seller): void
    {
        $this->em->persist($seller);
        $this->em->flush();
    }

    public function findById(string $id): ?Seller
    {
        return $this->em->getRepository(Seller::class)->find($id);
    }

    public function findByOwnerUserId(string $ownerUserId): ?Seller
    {
        return $this->em->getRepository(Seller::class)->findOneBy([
            'ownerUserId' => $ownerUserId,
        ]);
    }
}
