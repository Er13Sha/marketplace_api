<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Redis;

use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\Quantity;
use Predis\Client;

class StockRedisRepository implements StockRepositoryInterface
{
    private Client $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    private function key(CatalogProductId $productId): string
    {
        return 'stock:' . $productId->toString();
    }

    public function get(CatalogProductId $productId): ?Stock
    {
        $value = $this->redis->get($this->key($productId));
        if ($value === null) {
            return null;
        }
        return new Stock($productId, new Quantity((int) $value));
    }

    public function save(Stock $stock): void
    {
        $this->redis->set($this->key($stock->getProductId()), $stock->getQuantity()->getValue());
    }

    public function decrease(CatalogProductId $productId, Quantity $quantity): void
    {
        $lua = <<<LUA
            local key = KEYS[1]
            local qty = tonumber(ARGV[1])
            local current = tonumber(redis.call('GET', key) or 0)
            if current < qty then
                return -1
            end
            redis.call('DECRBY', key, qty)
            return 1
        LUA;

        $result = $this->redis->eval($lua, 1, $this->key($productId), $quantity->getValue());
        if ($result == -1) {
            throw new \DomainException('Insufficient stock');
        }
    }

    public function increase(CatalogProductId $productId, Quantity $quantity): void
    {
        $this->redis->incrby($this->key($productId), $quantity->getValue());
    }

    public function initialize(CatalogProductId $productId, Quantity $initialQuantity): void
    {
        $key = $this->key($productId);
        if (!$this->redis->exists($key)) {
            $this->redis->set($key, $initialQuantity->getValue());
        }
    }
}
