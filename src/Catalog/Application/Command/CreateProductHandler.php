<?php
declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(CreateProductCommand $command): void
    {
        $product = new Product(
            $command->sku,
            $command->name,
            $command->price,
            $command->stock,
            $command->description
        );
        $this->repository->save($product);

        // Публикуем доменные события, записанные агрегатом
        // (асинхронные подписчики: Elasticsearch, поиск, уведомления)
        foreach ($product->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
