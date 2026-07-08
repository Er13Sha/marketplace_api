<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Command\CreateProductCommand;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private CategoryRepositoryInterface $categoryRepository,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(CreateProductCommand $command): void
    {
        $category = null;
        if ($command->categoryId !== null) {
            $category = $this->categoryRepository->findById($command->categoryId);
            if (!$category) {
                throw new CategoryNotFoundException($command->categoryId);
            }
        }

        $product = new Product(
            $command->sku,
            $command->name,
            $command->price,
            $command->initialStock,
            $command->description,
            $category
        );
        $this->repository->save($product);

        // Публикуем доменные события, записанные агрегатом
        // (асинхронные подписчики: Elasticsearch, поиск, уведомления)
        foreach ($product->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
