<?php
declare(strict_types=1);

namespace App\Catalog\Application\Handler;

use App\Catalog\Application\Command\CreateCategoryCommand;
use App\Catalog\Domain\Entity\Category;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;

class CreateCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repository
    ) {}

    public function __invoke(CreateCategoryCommand $command): Category
    {
        if ($this->repository->findBySlug($command->slug)) {
            throw new \DomainException('Category slug already exists');
        }

        $category = new Category($command->name, $command->slug, $command->description);
        $this->repository->save($category);

        return $category;
    }
}
