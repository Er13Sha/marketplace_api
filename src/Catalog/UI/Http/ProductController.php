<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http;

use App\Catalog\Application\Command\CreateProductCommand;
use App\Catalog\Application\Query\GetProductQuery;
use App\Catalog\Application\Query\ListProductsQuery;
use App\Catalog\Application\Query\ProductView;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Catalog\Domain\ValueObject\Price;
use App\Catalog\UI\Http\Dto\CreateProductRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    public function __construct(
        // command.bus is the default bus → resolved via plain MessageBusInterface.
        private MessageBusInterface $commandBus,
        #[Target('query.bus')] private MessageBusInterface $queryBus
    ) {}

    #[Route('/api/catalog/products', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductRequest $request): JsonResponse
    {
        $command = new CreateProductCommand(
            new Sku($request->sku),
            $request->name,
            new Price($request->priceAmount, $request->currency),
            $request->stock,
            $request->description
        );

        $this->commandBus->dispatch($command);

        return $this->json(['status' => 'Product created'], Response::HTTP_CREATED);
    }

    #[Route('/api/catalog/products', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('q', ''));
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $envelope = $this->queryBus->dispatch(new ListProductsQuery(
            $name !== '' ? $name : null,
            $limit,
            $offset
        ));

        /** @var ProductView[] $views */
        $views = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'items' => array_map(
                static fn (ProductView $view): array => $view->toArray(),
                $views
            ),
            'count' => count($views),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    #[Route('/api/catalog/products/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        try {
            $productId = ProductId::fromString($id);
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Invalid product id'], Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(new GetProductQuery($productId));
        /** @var ProductView|null $view */
        $view = $envelope->last(HandledStamp::class)?->getResult();

        if (!$view) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($view->toArray());
    }
}
