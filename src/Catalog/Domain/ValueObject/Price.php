<?php
declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

final class Price implements \Stringable
{
    private int $amount;      // в минимальных единицах (копейки, центы)
    private string $currency; // ISO 4217

    public function __construct(int $amount, string $currency = 'KZT')
    {
        if ($amount < 0) throw new \InvalidArgumentException('Price cannot be negative');
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): int { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->amount, $this->currency);
    }
}
