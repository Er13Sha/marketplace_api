<?php
declare(strict_types=1);

namespace App\Inventory\Application\Handler;

use App\Inventory\Application\Command\IncreaseStockCommand;
use App\Inventory\Application\Command\LogStockOperationCommand;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class IncreaseStockHandler
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private MessageBusInterface $commandBus
    ) {}

    public function __invoke(IncreaseStockCommand $command): Stock
    {
        $this->stockRepository->increase($command->productId, $command->quantity);

        $this->commandBus->dispatch(new LogStockOperationCommand(
            $command->productId,
            $command->quantity->getValue(),
            'increased'
        ));

        return $this->stockRepository->get($command->productId)
            ?? new Stock($command->productId, $command->quantity);
    }
}
