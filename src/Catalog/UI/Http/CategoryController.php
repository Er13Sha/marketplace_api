<?php
declare(strict_types=1);

namespace App\Catalog\UI\Http;

use App\Catalog\Application\Command\CreateCategoryCommand;
use App\Catalog\Application\Query\GetCategoryQuery;
use App\Catalog\Application\Query\ListCategoriesQuery;
use App\Catalog\Application\ReadModel\CategoryView;
use App\Catalog\UI\Http\Dto\CreateCategoryRequest;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        #[Target('query.bus')] private MessageBusInterface $queryBus
    ) {}

    #[Route('/api/catalog/categories', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCategoryRequest $request): JsonResponse
    {
        try {
            $envelope = $this->commandBus->dispatch(new CreateCategoryCommand(
                $request->name,
                $request->slug,
                $request->description
            ));
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        $category = $envelope->last(HandledStamp::class)?->getResult();
        if (!$category) {
            return $this->json(['error' => 'Category was not created'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            CategoryView::fromEntity($category)->toArray(),
            Response::HTTP_CREATED
        );
    }

    #[Route('/api/catalog/categories', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $envelope = $this->queryBus->dispatch(new ListCategoriesQuery($limit, $offset));
        /** @var CategoryView[] $views */
        $views = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'items' => array_map(
                static fn (CategoryView $view): array => $view->toArray(),
                $views
            ),
            'count' => count($views),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    #[Route('/api/catalog/categories/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json(['error' => 'Invalid category id'], Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(new GetCategoryQuery($id));
        /** @var CategoryView|null $view */
        $view = $envelope->last(HandledStamp::class)?->getResult();

        if (!$view) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($view->toArray());
    }
}
