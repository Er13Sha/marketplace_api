<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return $this->json([
            'app'    => 'higload catalog API',
            'status' => 'ok',
            'endpoints' => [
                'create_product' => 'POST /api/catalog/products',
                'get_product'    => 'GET /api/catalog/products/{id}',
                'health'         => 'GET /health',
            ],
        ]);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
