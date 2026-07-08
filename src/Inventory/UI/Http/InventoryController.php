<?php
declare(strict_types=1);

namespace App\Inventory\UI\Http;

use App\Inventory\Application\Command\ReserveStockCommand;
use App\Inventory\Application\Command\CommitReservationCommand;
use App\Inventory\Application\Command\IncreaseStockCommand;
use App\Inventory\Application\Command\ReleaseReservationCommand;
use App\Inventory\Application\Query\GetStockQuery;
use App\Inventory\Domain\Entity\Stock;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Inventory\Domain\ValueObject\ReservationId;
use App\Inventory\UI\Http\DTO\ReserveRequest;
use App\Inventory\UI\Http\DTO\CommitRequest;
use App\Inventory\UI\Http\DTO\IncreaseStockRequest;
use App\Inventory\UI\Http\DTO\ReleaseRequest;
use App\Inventory\Domain\ValueObject\CatalogProductId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class InventoryController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        #[Target('query.bus')] private MessageBusInterface $queryBus
    ) {}

    #[Route('/api/inventory/reserve', methods: ['POST'])]
    public function reserve(#[MapRequestPayload] ReserveRequest $request): JsonResponse
    {
        $command = new ReserveStockCommand(
            CatalogProductId::fromString($request->productId),
            new Quantity($request->quantity),
            $request->ttlSeconds
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $reservation = $envelope->last(HandledStamp::class)?->getResult();

            if (!$reservation) {
                return $this->json(['error' => 'Reservation was not created'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->json([
                'reservation_id' => $reservation->getId()->toString(),
                'expires_at' => $reservation->getExpiresAt()->format(\DateTimeInterface::ATOM),
            ], Response::HTTP_CREATED);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/api/inventory/commit', methods: ['POST'])]
    public function commit(#[MapRequestPayload] CommitRequest $request): JsonResponse
    {
        $command = new CommitReservationCommand(ReservationId::fromString($request->reservationId));

        try {
            $this->commandBus->dispatch($command);
            return $this->json(['status' => 'committed']);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/api/inventory/release', methods: ['POST'])]
    public function release(#[MapRequestPayload] ReleaseRequest $request): JsonResponse
    {
        $command = new ReleaseReservationCommand(ReservationId::fromString($request->reservationId));

        try {
            $this->commandBus->dispatch($command);
            return $this->json(['status' => 'released']);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/api/inventory/stock/{productId}', methods: ['GET'])]
    public function getStock(string $productId): JsonResponse
    {
        $query = new GetStockQuery(CatalogProductId::fromString($productId));
        $envelope = $this->queryBus->dispatch($query);
        $stock = $envelope->last(HandledStamp::class)?->getResult();

        if (!$stock) {
            return $this->json(['error' => 'Product not found in inventory'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($stock);
    }

    #[Route('/api/inventory/stock/{productId}/increase', requirements: ['productId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function increaseStock(string $productId, #[MapRequestPayload] IncreaseStockRequest $request): JsonResponse
    {
        $command = new IncreaseStockCommand(
            CatalogProductId::fromString($productId),
            new Quantity($request->quantity)
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $stock = $envelope->last(HandledStamp::class)?->getResult();

            if (!$stock instanceof Stock) {
                return $this->json(['error' => 'Stock was not updated'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->json([
                'product_id' => $stock->getProductId()->toString(),
                'quantity' => $stock->getQuantity()->getValue(),
                'updated_at' => $stock->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ]);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}
