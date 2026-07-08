<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Entity;

use App\Catalog\Domain\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_reservations')]
#[ORM\Index(name: 'idx_inventory_reservations_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_inventory_reservations_expires_at', columns: ['expires_at'])]
final class InventoryReservationRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $committed;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $released;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;
}
