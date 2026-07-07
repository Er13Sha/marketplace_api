<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\CommitReservationCommand;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Event\StockCommittedEvent;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class CommitReservationHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(CommitReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);
        if (!$reservation) {
            throw new \DomainException('Reservation not found or expired');
        }
        if ($reservation->isExpired()) {
            throw new \DomainException('Reservation expired');
        }

        $reservation->commit();
        $this->reservationRepository->save($reservation);

        $this->eventBus->dispatch(new StockCommittedEvent(
            $reservation->getId(),
            $reservation->getProductId(),
            $reservation->getQuantity(),
            new \DateTimeImmutable()
        ));

        // Удаляем резерв (освобождаем память)
        $this->reservationRepository->delete($reservation->getId());
    }
}
