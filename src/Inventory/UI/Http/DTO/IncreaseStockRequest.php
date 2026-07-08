<?php
declare(strict_types=1);

namespace App\Inventory\UI\Http\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class IncreaseStockRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity;
}
