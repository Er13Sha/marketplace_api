<?php
declare(strict_types=1);

namespace App\Catalog\Infrastructure\Cache;

use App\Catalog\Application\Port\ProductCacheInterface;
use App\Catalog\Application\ReadModel\ProductView;
use App\Catalog\Domain\ValueObject\ProductId;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ProductCacheRedis implements ProductCacheInterface
{
    private const TTL = 300; // 5 минут

    public function __construct(private CacheInterface $cache) {}

    public function get(ProductId $id, callable $fetcher): ?ProductView
    {
        return $this->cache->get($this->key($id), function (ItemInterface $item) use ($fetcher): ?ProductView {
            $item->expiresAfter(self::TTL);
            return $fetcher();
        });
    }

    public function invalidate(ProductId $id): void
    {
        $this->cache->delete($this->key($id));
    }

    private function key(ProductId $id): string
    {
        return 'catalog_product_v4_' . $id->toString();
    }
}
