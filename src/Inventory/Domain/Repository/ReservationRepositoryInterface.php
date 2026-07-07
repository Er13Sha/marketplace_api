<?php
declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\ValueObject\ReservationId;

interface ReservationRepositoryInterface
{
    public function save(Reservation $reservation): void;
    public function findById(ReservationId $id): ?Reservation;
    public function delete(ReservationId $id): void;
}
