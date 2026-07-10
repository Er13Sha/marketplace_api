<?php
declare(strict_types=1);

namespace App\Cart\UI\Http;

use App\Auth\Domain\Entity\User;
use App\Cart\Application\Command\AddCartItemCommand;
use App\Cart\Application\Command\ClearCartCommand;
use App\Cart\Application\Command\RemoveCartItemCommand;
use App\Cart\Application\Command\UpdateCartItemCommand;
use App\Cart\Application\Query\GetCartQuery;
use App\Cart\Application\ReadModel\CartView;
use App\Cart\UI\Http\Dto\AddCartItemRequest;
use App\Cart\UI\Http\Dto\UpdateCartItemRequest;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Order\Application\Command\CheckoutOrderCommand;
use App\Order\Application\ReadModel\OrderView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        #[Target('query.bus')] private MessageBusInterface $queryBus
    ) {}

    #[Route('/api/cart', methods: ['GET'])]
    public function get(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetCartQuery($this->currentUser()->getId()));
        /** @var CartView $cart */
        $cart = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($cart->toArray());
    }

    #[Route('/api/cart/items', methods: ['POST'])]
    public function addItem(#[MapRequestPayload] AddCartItemRequest $request): JsonResponse
    {
        $productId = $this->parseProductId($request->productId);

        $envelope = $this->commandBus->dispatch(new AddCartItemCommand(
            $this->currentUser()->getId(),
            $productId,
            $request->quantity
        ));

        /** @var CartView $cart */
        $cart = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($cart->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/api/cart/items/{productId}', requirements: ['productId' => '[0-9a-fA-F-]{36}'], methods: ['PATCH'])]
    public function updateItem(string $productId, #[MapRequestPayload] UpdateCartItemRequest $request): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new UpdateCartItemCommand(
            $this->currentUser()->getId(),
            $this->parseProductId($productId),
            $request->quantity
        ));

        /** @var CartView $cart */
        $cart = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($cart->toArray());
    }

    #[Route('/api/cart/items/{productId}', requirements: ['productId' => '[0-9a-fA-F-]{36}'], methods: ['DELETE'])]
    public function removeItem(string $productId): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new RemoveCartItemCommand(
            $this->currentUser()->getId(),
            $this->parseProductId($productId)
        ));

        /** @var CartView $cart */
        $cart = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($cart->toArray());
    }

    #[Route('/api/cart', methods: ['DELETE'])]
    public function clear(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new ClearCartCommand($this->currentUser()->getId()));

        /** @var CartView $cart */
        $cart = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($cart->toArray());
    }

    #[Route('/api/cart/checkout', methods: ['POST'])]
    public function checkout(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CheckoutOrderCommand($this->currentUser()->getId()));

        /** @var OrderView $order */
        $order = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($order->toArray(), Response::HTTP_CREATED);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }

    private function parseProductId(string $productId): ProductId
    {
        try {
            return ProductId::fromString($productId);
        } catch (\InvalidArgumentException) {
            throw new BadRequestHttpException('Invalid product id.');
        }
    }
}
