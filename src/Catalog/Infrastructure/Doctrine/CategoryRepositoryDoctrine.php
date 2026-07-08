<?php
declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\Entity\Category;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CategoryRepositoryDoctrine implements CategoryRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Category $category): void
    {
        $this->em->persist($category);
        $this->em->flush();
    }

    public function findById(string $id): ?Category
    {
        return $this->em->getRepository(Category::class)->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
    }

    public function findAll(int $limit, int $offset): array
    {
        return $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
