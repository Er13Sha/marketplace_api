<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignProductCategoryRequest
{
    #[Assert\Uuid]
    public ?string $categoryId = null;
}
