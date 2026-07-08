<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Command\AssignProductCategoryCommand;
use App\Catalog\Application\Port\ProductCacheInterface;
use App\Catalog\Application\ReadModel\ProductView;
use App\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

final class AssignProductCategoryHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private ProductCacheInterface $productCache,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(AssignProductCategoryCommand $command): ProductView
    {
        $product = $this->productRepository->findById($command->productId);
        if (!$product) {
            throw new ProductNotFoundException($command->productId->toString());
        }

        $categoryId = $command->categoryId !== null ? trim($command->categoryId) : null;
        $category = null;
        if ($categoryId !== null && $categoryId !== '') {
            $category = $this->categoryRepository->findById($categoryId);
            if (!$category) {
                throw new CategoryNotFoundException($categoryId);
            }
        }

        $product->assignCategory($category);
        $this->productRepository->save($product);
        $this->productCache->invalidate($product->getId());

        foreach ($product->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return ProductView::fromEntity($product);
    }
}
