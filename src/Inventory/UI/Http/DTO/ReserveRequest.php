<?php
declare(strict_types=1);

namespace App\Inventory\UI\Http\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ReserveRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $productId;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity;

    #[Assert\Positive]
    public int $ttlSeconds = 900;
}
