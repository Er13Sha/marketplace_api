<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Entity;

use App\Catalog\Domain\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_stock')]
final class InventoryStockRecord
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;
}
