<?php
declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\ValueObject\Sku;
use PHPUnit\Framework\TestCase;

final class SkuTest extends TestCase
{
    public function testAcceptsValidSku(): void
    {
        $sku = new Sku('ABC12345');

        self::assertSame('ABC12345', $sku->toString());
        self::assertSame('ABC12345', (string) $sku);
    }

    public function testRejectsLowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Sku('abc12345');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Sku('ABC1');
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue((new Sku('ABC12345'))->equals(new Sku('ABC12345')));
        self::assertFalse((new Sku('ABC12345'))->equals(new Sku('XYZ98765')));
    }
}
