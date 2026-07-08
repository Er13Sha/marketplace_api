<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Domain\Event\StockCommittedEvent;
use App\Inventory\Infrastructure\Doctrine\Entity\StockLog;
use App\Inventory\Infrastructure\Doctrine\Repository\StockLogDoctrineRepository;

final class LogStockCommittedEventHandler
{
    public function __construct(
        private StockLogDoctrineRepository $logRepository
    ) {}

    public function __invoke(StockCommittedEvent $event): void
    {
        $this->logRepository->save(new StockLog(
            $event->productId->toString(),
            $event->quantity->getValue(),
            'committed',
            $event->reservationId->toString(),
            ['occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM)]
        ));
    }
}
