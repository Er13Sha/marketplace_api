<?php
declare(strict_types=1);

namespace App\Seller\Domain\Repository;

use App\Seller\Domain\Entity\Seller;

interface SellerRepositoryInterface
{
    public function save(Seller $seller): void;

    public function findById(string $id): ?Seller;

    public function findByOwnerUserId(string $ownerUserId): ?Seller;
}
