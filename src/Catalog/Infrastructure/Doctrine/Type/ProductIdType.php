<?php
declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine\Type;

use App\Catalog\Domain\ValueObject\ProductId;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ramsey\Uuid\Uuid;

class ProductIdType extends Type
{
    public const NAME = 'product_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductId
    {
        return $value ? new ProductId(Uuid::fromString($value)) : null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof ProductId ? $value->toString() : null;
    }

    public function getName(): string { return self::NAME; }
}
