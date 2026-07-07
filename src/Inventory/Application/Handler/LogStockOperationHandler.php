<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\LogStockOperationCommand;
use App\Inventory\Infrastructure\Doctrine\Entity\StockLog;
use App\Inventory\Infrastructure\Doctrine\Repository\StockLogDoctrineRepository;

class LogStockOperationHandler
{
    public function __construct(
        private StockLogDoctrineRepository $logRepository
    ) {}

    public function __invoke(LogStockOperationCommand $command): void
    {
        $log = new StockLog(
            $command->productId->toString(),
            $command->quantity,
            $command->operation,
            $command->reservationId,
            $command->metadata
        );
        $this->logRepository->save($log);
    }
}
