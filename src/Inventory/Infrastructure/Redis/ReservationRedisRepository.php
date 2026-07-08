<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Redis;

use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\ValueObject\ReservationId;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use Predis\Client;

class ReservationRedisRepository implements ReservationRepositoryInterface
{
    private Client $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    private function key(ReservationId $id): string
    {
        return 'reservation:' . $id->toString();
    }

    public function save(Reservation $reservation): void
    {
        $data = [
            'product_id' => $reservation->getProductId()->toString(),
            'quantity' => $reservation->getQuantity()->getValue(),
            'expires_at' => $reservation->getExpiresAt()->getTimestamp(),
            'committed' => (int) $reservation->isCommitted(),
            'released' => (int) $reservation->isReleased(),
        ];
        $key = $this->key($reservation->getId());
        $this->redis->set($key, json_encode($data));

        $ttlSeconds = $reservation->getExpiresAt()->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();
        if ($ttlSeconds > 0) {
            $this->redis->expire($key, $ttlSeconds);
        } else {
            $this->redis->del($key);
        }
    }

    public function findById(ReservationId $id): ?Reservation
    {
        $data = $this->redis->get($this->key($id));
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return null;
        }

        foreach (['product_id', 'quantity', 'expires_at', 'committed', 'released'] as $field) {
            if (!array_key_exists($field, $decoded)) {
                return null;
            }
        }

        return Reservation::restore(
            $id,
            CatalogProductId::fromString((string) $decoded['product_id']),
            new Quantity((int) $decoded['quantity']),
            (new \DateTimeImmutable())->setTimestamp((int) $decoded['expires_at']),
            (bool) $decoded['committed'],
            (bool) $decoded['released']
        );
    }

    public function delete(ReservationId $id): void
    {
        $this->redis->del($this->key($id));
    }
}
