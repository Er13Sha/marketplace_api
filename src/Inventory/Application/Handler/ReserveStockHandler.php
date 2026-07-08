<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\ReserveStockCommand;
use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Event\StockReservedEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class ReserveStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private ReservationRepositoryInterface $reservationRepository,
        private Connection $connection,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(ReserveStockCommand $command): Reservation
    {
        $reservation = $this->connection->transactional(function () use ($command): Reservation {
            $this->stockRepository->decrease($command->productId, $command->quantity);

            $ttl = new \DateInterval('PT' . $command->ttlSeconds . 'S');
            $reservation = new Reservation($command->productId, $command->quantity, $ttl);
            $this->reservationRepository->save($reservation);

            return $reservation;
        });

        $this->eventBus->dispatch(new StockReservedEvent(
            $command->productId,
            $command->quantity,
            $reservation->getId(),
            new \DateTimeImmutable()
        ));

        return $reservation;
    }
}
