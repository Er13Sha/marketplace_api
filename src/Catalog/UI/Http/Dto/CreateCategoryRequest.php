<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http\Dto;

use App\Catalog\Domain\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class CreateCategoryRequest
{
    #[Assert\NotBlank, Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank, Assert\Length(max: 120), Assert\Regex(pattern: Category::SLUG_PATTERN)]
    public string $slug;

    public ?string $description = null;
}
