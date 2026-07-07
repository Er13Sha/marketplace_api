<?php
declare(strict_types=1);

namespace App\Inventory\UI\Http\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CommitRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $reservationId;
}
