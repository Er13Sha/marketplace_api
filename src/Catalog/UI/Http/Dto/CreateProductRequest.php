<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http\Dto;

use App\Catalog\Domain\ValueObject\Sku;
use Symfony\Component\Validator\Constraints as Assert;

class CreateProductRequest
{
    #[Assert\NotBlank, Assert\Regex(pattern: Sku::PATTERN)]
    public string $sku;

    #[Assert\NotBlank, Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank, Assert\Positive]
    public int $priceAmount;

    #[Assert\NotBlank, Assert\Currency]
    public string $currency = 'KZT';

    #[Assert\NotBlank, Assert\PositiveOrZero]
    public int $stock;

    public ?string $description = null;
}
