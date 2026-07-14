<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http;

use App\Catalog\Application\Command\AssignProductCategoryCommand;
use App\Catalog\Application\Command\CreateProductCommand;
use App\Catalog\Application\Query\GetProductQuery;
use App\Catalog\Application\Query\ListProductsQuery;
use App\Catalog\Application\ReadModel\ProductListView;
use App\Catalog\Application\ReadModel\ProductView;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Catalog\Domain\ValueObject\Price;
use App\Catalog\UI\Http\Dto\AssignProductCategoryRequest;
use App\Catalog\UI\Http\Dto\CreateProductRequest;
use App\Auth\Domain\Entity\User;
use App\Seller\Domain\Exception\SellerProfileRequiredException;
use App\Seller\Domain\Repository\SellerRepositoryInterface;
use App\Shared\Domain\Exception\AppException;
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
        #[Target('query.bus')] private MessageBusInterface $queryBus,
        private SellerRepositoryInterface $sellers
    ) {}

    #[Route('/api/catalog/products', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductRequest $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Authentication required.',
                'code' => 'authentication_required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $seller = $this->sellers->findByOwnerUserId($user->getId());
        if ((!$seller || !$seller->isActive()) && !$user->hasRole(User::ROLE_ADMIN)) {
            throw new SellerProfileRequiredException();
        }

        $command = new CreateProductCommand(
            new Sku($request->sku),
            $request->name,
            new Price($request->priceAmount, $request->currency),
            $request->stock,
            $request->description,
            $request->categoryId,
            $seller?->getId()
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (AppException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $e->statusCode());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'Product created'], Response::HTTP_CREATED);
    }

    #[Route('/api/catalog/products', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('q', ''));
        $categoryId = trim((string) $request->query->get('categoryId', ''));
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $envelope = $this->queryBus->dispatch(new ListProductsQuery(
            $name !== '' ? $name : null,
            $categoryId !== '' ? $categoryId : null,
            null,
            $limit,
            $offset
        ));

        /** @var ProductListView|null $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();
        $views = $result?->items ?? [];

        return $this->json([
            'items' => array_map(
                static fn (ProductView $view): array => $view->toArray(),
                $views
            ),
            'count' => count($views),
            'total' => $result?->total ?? count($views),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    #[Route('/api/catalog/products/mine', methods: ['GET'])]
    public function listMine(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Authentication required.',
                'code' => 'authentication_required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $name = trim((string) $request->query->get('q', ''));
        $categoryId = trim((string) $request->query->get('categoryId', ''));
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));
        $seller = $this->sellers->findByOwnerUserId($user->getId());
        if (!$seller || !$seller->isActive()) {
            throw new SellerProfileRequiredException();
        }

        $envelope = $this->queryBus->dispatch(new ListProductsQuery(
            $name !== '' ? $name : null,
            $categoryId !== '' ? $categoryId : null,
            $seller->getId(),
            $limit,
            $offset
        ));

        /** @var ProductListView|null $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();
        $views = $result?->items ?? [];

        return $this->json([
            'items' => array_map(
                static fn (ProductView $view): array => $view->toArray(),
                $views
            ),
            'count' => count($views),
            'total' => $result?->total ?? count($views),
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

    #[Route('/api/catalog/products/{id}/category', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['PATCH'])]
    public function assignCategory(string $id, #[MapRequestPayload] AssignProductCategoryRequest $request): JsonResponse
    {
        try {
            $productId = ProductId::fromString($id);
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Invalid product id'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->commandBus->dispatch(new AssignProductCategoryCommand(
                $productId,
                $request->categoryId
            ));
        } catch (AppException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $e->statusCode());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        /** @var ProductView|null $view */
        $view = $envelope->last(HandledStamp::class)?->getResult();

        if (!$view) {
            return $this->json(['error' => 'Category was not assigned'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($view->toArray());
    }
}
