<?php
declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

final class Sku implements \Stringable
{
    public const PATTERN = '/^[A-Z0-9]{8,20}$/';

    private string $value;

    public function __construct(string $value)
    {
        if (!preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Invalid SKU format');
        }
        $this->value = $value;
    }

    public function toString(): string { return $this->value; }
    public function __toString(): string { return $this->value; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
