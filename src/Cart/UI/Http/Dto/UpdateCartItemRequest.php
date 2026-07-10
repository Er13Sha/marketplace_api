<?php
declare(strict_types=1);

namespace App\Cart\UI\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCartItemRequest
{
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public int $quantity;
}
