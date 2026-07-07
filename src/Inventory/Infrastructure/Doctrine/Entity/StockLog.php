<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_log')]
class StockLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 50)]
    private string $operation;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $reservationId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(
        string $productId,
        int $quantity,
        string $operation,
        ?string $reservationId = null,
        ?array $metadata = null
    ) {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->operation = $operation;
        $this->reservationId = $reservationId;
        $this->metadata = $metadata;
        $this->occurredAt = new \DateTimeImmutable();
    }

    // Геттеры для чтения (опущены для краткости)
}
