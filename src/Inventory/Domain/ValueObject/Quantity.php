<?php
declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

/**
 * Количество товара. Всегда неотрицательное целое.
 */
final class Quantity
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative');
        }
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
