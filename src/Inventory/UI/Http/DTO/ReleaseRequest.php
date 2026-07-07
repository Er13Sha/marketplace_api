<?php
declare(strict_types=1);

namespace App\Inventory\UI\Http\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ReleaseRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $reservationId;
}
