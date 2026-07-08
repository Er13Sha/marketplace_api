<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\CommitReservationCommand;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Event\StockCommittedEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class CommitReservationHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private StockRepositoryInterface $stockRepository,
        private Connection $connection,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(CommitReservationCommand $command): void
    {
        $committedReservation = null;
        $expired = false;

        $this->connection->transactional(function () use ($command, &$committedReservation, &$expired): void {
            $reservation = $this->reservationRepository->findById($command->reservationId);
            if (!$reservation) {
                throw new \DomainException('Reservation not found');
            }
            if ($reservation->isCommitted() || $reservation->isReleased()) {
                throw new \DomainException('Reservation already finalized');
            }
            if ($reservation->isExpired()) {
                $reservation->release();
                $this->stockRepository->increase($reservation->getProductId(), $reservation->getQuantity());
                $this->reservationRepository->save($reservation);
                $expired = true;

                return;
            }

            $reservation->commit();
            $this->reservationRepository->save($reservation);
            $committedReservation = $reservation;
        });

        if ($expired) {
            throw new \DomainException('Reservation expired');
        }

        if (!$committedReservation instanceof Reservation) {
            throw new \DomainException('Reservation was not committed');
        }

        $this->eventBus->dispatch(new StockCommittedEvent(
            $committedReservation->getId(),
            $committedReservation->getProductId(),
            $committedReservation->getQuantity(),
            new \DateTimeImmutable()
        ));
    }
}
