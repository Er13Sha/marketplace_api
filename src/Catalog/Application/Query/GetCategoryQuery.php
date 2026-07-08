<?php
declare(strict_types=1);

namespace App\Catalog\Application\Query;

class GetCategoryQuery
{
    public function __construct(public readonly string $id) {}
}
