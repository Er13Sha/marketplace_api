<?php
declare(strict_types=1);

namespace App\Order\UI\Http;

use App\Auth\Domain\Entity\User;
use App\Order\Application\Command\CheckoutOrderCommand;
use App\Order\Application\Query\GetOrderQuery;
use App\Order\Application\Query\ListOrdersQuery;
use App\Order\Application\ReadModel\OrderView;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        #[Target('query.bus')] private MessageBusInterface $queryBus
    ) {}

    #[Route('/api/orders/checkout', methods: ['POST'])]
    public function checkout(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CheckoutOrderCommand($this->currentUser()->getId()));

        /** @var OrderView $order */
        $order = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($order->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/api/orders', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $envelope = $this->queryBus->dispatch(new ListOrdersQuery(
            $this->currentUser()->getId(),
            $limit,
            $offset
        ));

        /** @var OrderView[] $orders */
        $orders = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'items' => array_map(
                static fn (OrderView $order): array => $order->toArray(),
                $orders
            ),
            'count' => count($orders),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    #[Route('/api/orders/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            throw new BadRequestHttpException('Invalid order id.');
        }

        $envelope = $this->queryBus->dispatch(new GetOrderQuery(
            $this->currentUser()->getId(),
            $id
        ));

        /** @var OrderView $order */
        $order = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($order->toArray());
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }
}
