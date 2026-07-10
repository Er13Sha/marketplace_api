<?php
declare(strict_types=1);

namespace App\Cart\UI\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AddCartItemRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $productId;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity = 1;
}
