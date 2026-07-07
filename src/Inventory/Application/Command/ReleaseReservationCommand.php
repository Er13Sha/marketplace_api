<?php
declare(strict_types=1);

namespace App\Inventory\Application\Command;

use App\Inventory\Domain\ValueObject\ReservationId;

class ReleaseReservationCommand
{
    public function __construct(
        public readonly ReservationId $reservationId
    )
    {
    }
}
