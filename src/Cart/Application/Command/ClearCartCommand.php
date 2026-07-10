<?php
declare(strict_types=1);

namespace App\Cart\Application\Command;

final class ClearCartCommand
{
    public function __construct(
        public readonly string $userId
    ) {}
}
