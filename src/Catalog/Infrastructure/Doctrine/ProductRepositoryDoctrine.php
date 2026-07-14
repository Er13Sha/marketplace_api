<?php
declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ProductRepositoryDoctrine implements ProductRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function findById(ProductId $id): ?Product
    {
        return $this->em->getRepository(Product::class)->find($id);
    }

    public function findBySku(Sku $sku): ?Product
    {
        return $this->em->getRepository(Product::class)->findOneBy(['sku.value' => $sku->toString()]);
    }

    public function delete(ProductId $id): void
    {
        $product = $this->findById($id);
        if ($product) {
            $this->em->remove($product);
            $this->em->flush();
        }
    }

    /**
     * @return Product[]
     */
    public function findByCriteria(array $filters, int $limit, int $offset): array
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyCriteria($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    public function countByCriteria(array $filters): int
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        $this->applyCriteria($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyCriteria(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(p.name) LIKE :name OR LOWER(p.sku.value) LIKE :name')
                ->setParameter('name', '%' . strtolower((string) $filters['name']) . '%');
        }

        if (!empty($filters['categoryId'])) {
            $qb->andWhere('IDENTITY(p.category) = :categoryId')
                ->setParameter('categoryId', $filters['categoryId']);
        }

        if (!empty($filters['sellerId'])) {
            $qb->andWhere('p.sellerId = :sellerId')
                ->setParameter('sellerId', $filters['sellerId']);
        }
    }
}
