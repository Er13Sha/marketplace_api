<?php
declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\ValueObject\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Price(-1, 'KZT');
    }

    public function testDefaultsToKzt(): void
    {
        self::assertSame('KZT', (new Price(1000))->getCurrency());
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue((new Price(1000, 'KZT'))->equals(new Price(1000, 'KZT')));
        self::assertFalse((new Price(1000, 'KZT'))->equals(new Price(2000, 'KZT')));
        self::assertFalse((new Price(1000, 'KZT'))->equals(new Price(1000, 'USD')));
    }
}
