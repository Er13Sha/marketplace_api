<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\ReleaseReservationCommand;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Repository\StockRepositoryInterface;

final class ReleaseReservationHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private StockRepositoryInterface $stockRepository
    ) {}

    public function __invoke(ReleaseReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);
        if (!$reservation) {
            throw new \DomainException('Reservation not found or expired');
        }

        $reservation->release();
        $this->stockRepository->increase($reservation->getProductId(), $reservation->getQuantity());
        $this->reservationRepository->delete($reservation->getId());
    }
}
