<?php
declare(strict_types=1);

namespace App\Catalog\Application\Command;

class CreateCategoryCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description
    ) {}
}
