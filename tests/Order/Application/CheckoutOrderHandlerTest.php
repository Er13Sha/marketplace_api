<?php
declare(strict_types=1);

namespace App\Tests\Order\Application;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Exception\EmptyCartException;
use App\Cart\Domain\Exception\InsufficientCartStockException;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\Price;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\ReservationId;
use App\Order\Application\Command\CheckoutOrderCommand;
use App\Order\Application\Handler\CheckoutOrderHandler;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CheckoutOrderHandlerTest extends TestCase
{
    public function testCheckoutCreatesOrderDecreasesStockAndClosesCart(): void
    {
        $userId = '11111111-1111-4111-8111-111111111111';
        $product = new Product(new Sku('CHKOUT01'), 'Checkout Product', new Price(2500, 'KZT'), 5);
        $cart = new Cart($userId);
        $cart->addItem($product->getId(), 2);

        $cartRepository = new CheckoutCartRepository($cart);
        $productRepository = new CheckoutProductRepository($product);
        $stockRepository = new CheckoutStockRepository([
            $product->getId()->toString() => 5,
        ]);
        $reservationRepository = new CheckoutReservationRepository();
        $orderRepository = new CheckoutOrderRepository();
        $eventBus = new CheckoutEventBus();

        $handler = new CheckoutOrderHandler(
            $cartRepository,
            $productRepository,
            $stockRepository,
            $reservationRepository,
            $orderRepository,
            $this->transactionalConnection(1),
            $eventBus
        );

        $view = $handler(new CheckoutOrderCommand($userId));

        self::assertSame('created', $view->status);
        self::assertSame($userId, $view->userId);
        self::assertSame($cart->getId(), $view->cartId);
        self::assertSame(2, $view->itemsCount);
        self::assertSame(5000, $view->total);
        self::assertSame('KZT', $view->currency);
        self::assertCount(1, $view->items);
        self::assertCount(1, $view->reservationIds);

        self::assertSame(3, $stockRepository->quantityFor($product->getId()));
        self::assertCount(1, $reservationRepository->savedReservations);
        self::assertCount(1, $orderRepository->savedOrders);
        self::assertSame(Order::STATUS_CREATED, array_values($orderRepository->savedOrders)[0]->getStatus());
        self::assertSame(Cart::STATUS_CHECKED_OUT, $cart->getStatus());
        self::assertCount(2, $eventBus->messages);
    }

    public function testCheckoutRejectsEmptyCart(): void
    {
        $userId = '11111111-1111-4111-8111-111111111111';

        $handler = new CheckoutOrderHandler(
            new CheckoutCartRepository(new Cart($userId)),
            new CheckoutProductRepository(),
            new CheckoutStockRepository(),
            new CheckoutReservationRepository(),
            new CheckoutOrderRepository(),
            $this->transactionalConnection(0),
            new CheckoutEventBus()
        );

        $this->expectException(EmptyCartException::class);
        $handler(new CheckoutOrderCommand($userId));
    }

    public function testCheckoutRejectsInsufficientStock(): void
    {
        $userId = '11111111-1111-4111-8111-111111111111';
        $product = new Product(new Sku('LOWSTOCK'), 'Low Stock Product', new Price(1000, 'KZT'), 1);
        $cart = new Cart($userId);
        $cart->addItem($product->getId(), 2);

        $handler = new CheckoutOrderHandler(
            new CheckoutCartRepository($cart),
            new CheckoutProductRepository($product),
            new CheckoutStockRepository([
                $product->getId()->toString() => 1,
            ]),
            new CheckoutReservationRepository(),
            new CheckoutOrderRepository(),
            $this->transactionalConnection(1),
            new CheckoutEventBus()
        );

        $this->expectException(InsufficientCartStockException::class);
        $handler(new CheckoutOrderCommand($userId));
    }

    private function transactionalConnection(int $expectedCalls): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly($expectedCalls))
            ->method('transactional')
            ->willReturnCallback(static fn (\Closure $callback): mixed => $callback());

        return $connection;
    }
}

final class CheckoutCartRepository implements CartRepositoryInterface
{
    public function __construct(private ?Cart $activeCart = null) {}

    public function save(Cart $cart): void
    {
        $this->activeCart = $cart;
    }

    public function findById(string $id): ?Cart
    {
        return $this->activeCart?->getId() === $id ? $this->activeCart : null;
    }

    public function findActiveByUserId(string $userId): ?Cart
    {
        return $this->activeCart?->getUserId() === $userId && $this->activeCart->isActive()
            ? $this->activeCart
            : null;
    }
}

final class CheckoutProductRepository implements ProductRepositoryInterface
{
    /** @var array<string, Product> */
    private array $products = [];

    public function __construct(Product ...$products)
    {
        foreach ($products as $product) {
            $this->products[$product->getId()->toString()] = $product;
        }
    }

    public function save(Product $product): void
    {
        $this->products[$product->getId()->toString()] = $product;
    }

    public function findById(ProductId $id): ?Product
    {
        return $this->products[$id->toString()] ?? null;
    }

    public function findBySku(Sku $sku): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->getSku()->equals($sku)) {
                return $product;
            }
        }

        return null;
    }

    public function delete(ProductId $id): void
    {
        unset($this->products[$id->toString()]);
    }

    public function findByCriteria(array $filters, int $limit, int $offset): array
    {
        return array_slice(array_values($this->products), $offset, $limit);
    }
}

final class CheckoutStockRepository implements StockRepositoryInterface
{
    /** @param array<string,int> $quantities */
    public function __construct(private array $quantities = []) {}

    public function get(CatalogProductId $productId): ?Stock
    {
        $quantity = $this->quantities[$productId->toString()] ?? null;
        if ($quantity === null) {
            return null;
        }

        return new Stock($productId, new Quantity($quantity));
    }

    public function save(Stock $stock): void
    {
        $this->quantities[$stock->getProductId()->toString()] = $stock->getQuantity()->getValue();
    }

    public function decrease(CatalogProductId $productId, Quantity $quantity): void
    {
        $currentQuantity = $this->quantities[$productId->toString()] ?? 0;
        if ($currentQuantity < $quantity->getValue()) {
            throw new \DomainException('Insufficient stock');
        }

        $this->quantities[$productId->toString()] = $currentQuantity - $quantity->getValue();
    }

    public function increase(CatalogProductId $productId, Quantity $quantity): void
    {
        $this->quantities[$productId->toString()] = ($this->quantities[$productId->toString()] ?? 0)
            + $quantity->getValue();
    }

    public function initialize(CatalogProductId $productId, Quantity $initialQuantity): void
    {
        $this->quantities[$productId->toString()] ??= $initialQuantity->getValue();
    }

    public function quantityFor(ProductId $productId): int
    {
        return $this->quantities[$productId->toString()] ?? 0;
    }
}

final class CheckoutReservationRepository implements ReservationRepositoryInterface
{
    /** @var Reservation[] */
    public array $savedReservations = [];

    public function save(Reservation $reservation): void
    {
        $this->savedReservations[$reservation->getId()->toString()] = $reservation;
    }

    public function findById(ReservationId $id): ?Reservation
    {
        return $this->savedReservations[$id->toString()] ?? null;
    }

    public function delete(ReservationId $id): void
    {
        unset($this->savedReservations[$id->toString()]);
    }
}

final class CheckoutOrderRepository implements OrderRepositoryInterface
{
    /** @var Order[] */
    public array $savedOrders = [];

    public function save(Order $order): void
    {
        $this->savedOrders[$order->getId()] = $order;
    }

    public function findById(string $id): ?Order
    {
        return $this->savedOrders[$id] ?? null;
    }

    public function findByIdForUser(string $id, string $userId): ?Order
    {
        $order = $this->findById($id);

        return $order?->getUserId() === $userId ? $order : null;
    }

    public function findByUserId(string $userId, int $limit, int $offset): array
    {
        $orders = array_values(array_filter(
            $this->savedOrders,
            static fn (Order $order): bool => $order->getUserId() === $userId
        ));

        return array_slice($orders, $offset, $limit);
    }
}

final class CheckoutEventBus implements MessageBusInterface
{
    /** @var object[] */
    public array $messages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->messages[] = $message;

        return new Envelope($message, $stamps);
    }
}
