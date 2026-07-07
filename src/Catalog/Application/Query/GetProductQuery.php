<?php
declare(strict_types=1);

namespace App\Catalog\Application\Query;

use App\Catalog\Domain\ValueObject\ProductId;

class GetProductQuery
{
    public function __construct(public readonly ProductId $id) {}
}
