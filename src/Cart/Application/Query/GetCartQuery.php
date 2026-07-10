<?php
declare(strict_types=1);

namespace App\Cart\Application\Query;

final class GetCartQuery
{
    public function __construct(
        public readonly string $userId
    ) {}
}
