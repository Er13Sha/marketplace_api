<?php
declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Cart\Domain\Exception\EmptyCartException;
use App\Cart\Domain\Exception\InsufficientCartStockException;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Inventory\Domain\Entity\Reservation;
use App\Inventory\Domain\Event\StockCommittedEvent;
use App\Inventory\Domain\Event\StockReservedEvent;
use App\Inventory\Domain\Repository\ReservationRepositoryInterface;
use App\Inventory\Domain\Repository\StockRepositoryInterface;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Order\Application\Command\CheckoutOrderCommand;
use App\Order\Application\ReadModel\OrderView;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

final class CheckoutOrderHandler
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private StockRepositoryInterface $stock,
        private ReservationRepositoryInterface $reservations,
        private OrderRepositoryInterface $orders,
        private Connection $connection,
        #[Target('event.bus')] private MessageBusInterface $eventBus
    ) {}

    public function __invoke(CheckoutOrderCommand $command): OrderView
    {
        $cart = $this->carts->findActiveByUserId($command->userId);
        if (!$cart || $cart->getItemsCount() === 0) {
            throw new EmptyCartException();
        }

        $createdReservations = [];
        $order = null;

        $this->connection->transactional(function () use ($cart, $command, &$createdReservations, &$order): void {
            $order = new Order($command->userId, $cart->getId());

            foreach ($cart->getItems() as $cartItem) {
                $product = $this->products->findById($cartItem->getProductId());
                if (!$product) {
                    throw new ProductNotFoundException($cartItem->getProductId()->toString());
                }

                $productId = CatalogProductId::fromString($cartItem->getProductId()->toString());
                $quantity = new Quantity($cartItem->getQuantity());

                try {
                    $this->stock->decrease($productId, $quantity);
                } catch (\DomainException) {
                    $availableQuantity = $this->stock->get($productId)?->getQuantity()->getValue() ?? 0;
                    throw new InsufficientCartStockException($productId->toString(), $availableQuantity);
                }

                $reservation = new Reservation($productId, $quantity, new \DateInterval('PT15M'));
                $reservation->commit();
                $this->reservations->save($reservation);
                $createdReservations[] = $reservation;

                $order->addItem(
                    productId: $product->getId()->toString(),
                    productSku: $product->getSku()->toString(),
                    productName: $product->getName(),
                    priceAmount: $product->getPrice()->getAmount(),
                    currency: $product->getPrice()->getCurrency(),
                    quantity: $cartItem->getQuantity(),
                    reservationId: $reservation->getId()->toString()
                );
            }

            $this->orders->save($order);

            $cart->checkout();
            $this->carts->save($cart);
        });

        if (!$order instanceof Order) {
            throw new \RuntimeException('Order was not created.');
        }

        foreach ($createdReservations as $reservation) {
            $occurredAt = new \DateTimeImmutable();
            $this->eventBus->dispatch(new StockReservedEvent(
                $reservation->getProductId(),
                $reservation->getQuantity(),
                $reservation->getId(),
                $occurredAt
            ));
            $this->eventBus->dispatch(new StockCommittedEvent(
                $reservation->getId(),
                $reservation->getProductId(),
                $reservation->getQuantity(),
                $occurredAt
            ));
        }

        return OrderView::fromEntity($order);
    }
}
