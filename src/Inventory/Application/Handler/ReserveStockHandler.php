<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\ReserveStockCommand;
use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Event\StockReservedEvent;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class ReserveStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private ReservationRepositoryInterface $reservationRepository,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(ReserveStockCommand $command): Reservation
    {
        // 1. Атомарно уменьшаем остаток (Lua-скрипт)
        $this->stockRepository->decrease($command->productId, $command->quantity);

        // 2. Создаём резерв
        $ttl = new \DateInterval('PT' . $command->ttlSeconds . 'S');
        $reservation = new Reservation($command->productId, $command->quantity, $ttl);
        $this->reservationRepository->save($reservation);

        // 3. Публикуем событие (асинхронно, для аудита)
        $this->eventBus->dispatch(new StockReservedEvent(
            $command->productId,
            $command->quantity,
            $reservation->getId(),
            new \DateTimeImmutable()
        ));

        return $reservation;
    }
}
