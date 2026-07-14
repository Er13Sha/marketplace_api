<?php
declare(strict_types=1);

namespace App\Catalog\Infrastructure\Symfony\Command;

use Doctrine\DBAL\Connection;
use Faker\Factory;
use Faker\Generator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-demo-products',
    description: 'Seeds the catalog with fake demo products for a fresh installation.'
)]
final class SeedDemoProductsCommand extends Command
{
    private const DEFAULT_COUNT = 20000;
    private const DEFAULT_BATCH_SIZE = 1000;
    private const DEFAULT_SEED = 20260710;

    private const DEMO_CATEGORIES = [
        ['Electronics', 'demo-electronics', 'Phones, laptops, accessories, and connected devices.'],
        ['Home Appliances', 'demo-home-appliances', 'Useful appliances for everyday home routines.'],
        ['Sports', 'demo-sports', 'Training gear, outdoor equipment, and activewear.'],
        ['Beauty', 'demo-beauty', 'Care products, fragrances, and grooming essentials.'],
        ['Books', 'demo-books', 'Fiction, non-fiction, education, and reference books.'],
        ['Toys', 'demo-toys', 'Toys, puzzles, games, and creative kits.'],
        ['Auto', 'demo-auto', 'Car accessories, tools, fluids, and maintenance parts.'],
        ['Office', 'demo-office', 'Workplace supplies, stationery, and office equipment.'],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Number of products to add.', (string) self::DEFAULT_COUNT)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Rows inserted per batch.', (string) self::DEFAULT_BATCH_SIZE)
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Deterministic fake-data seed.', (string) self::DEFAULT_SEED)
            ->addOption('if-empty', null, InputOption::VALUE_NONE, 'Skip seeding when the catalog is not empty.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Deprecated no-op; products are appended by default.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->positiveIntOption($input, 'count');
        $batchSize = $this->positiveIntOption($input, 'batch-size');
        $seed = $this->positiveIntOption($input, 'seed');

        if ($count === null || $batchSize === null || $seed === null) {
            $io->error('Options --count, --batch-size, and --seed must be positive integers.');
            return Command::FAILURE;
        }

        if ($batchSize > 5000) {
            $io->error('Option --batch-size must not be greater than 5000.');
            return Command::FAILURE;
        }

        $existingProducts = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM catalog_products');
        if ($input->getOption('if-empty') === true && $existingProducts > 0) {
            $io->success(sprintf(
                'Catalog already contains %d product(s); demo seed skipped.',
                $existingProducts
            ));
            return Command::SUCCESS;
        }

        $productsToCreate = $count;
        $faker = Factory::create('en_US');
        $faker->seed($seed);
        $startedAt = microtime(true);

        $inserted = $this->connection->transactional(function () use ($productsToCreate, $batchSize, $existingProducts, $faker): int {
            $categoryIds = $this->ensureCategoryIds();
            $skuBase = max($this->nextFakeSkuOffset(), $existingProducts);
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $inserted = 0;

            while ($inserted < $productsToCreate) {
                $currentBatchSize = min($batchSize, $productsToCreate - $inserted);
                $products = [];

                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $number = $skuBase + $inserted + $i + 1;
                    $products[] = $this->fakeProduct($number, $categoryIds, $now, $faker);
                }

                $this->insertProducts($products);
                $inserted += $currentBatchSize;
            }

            return $inserted;
        });

        $io->success(sprintf(
            'Seeded %d fake product(s) in %.2f seconds.',
            $inserted,
            microtime(true) - $startedAt
        ));

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function ensureCategoryIds(): array
    {
        $categoryCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM catalog_categories');

        if ($categoryCount === 0) {
            foreach (self::DEMO_CATEGORIES as [$name, $slug, $description]) {
                $this->connection->executeStatement(
                    <<<'SQL'
INSERT INTO catalog_categories (id, name, slug, description, created_at, updated_at)
VALUES (:id, :name, :slug, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (slug) DO NOTHING
SQL,
                    [
                        'id' => Uuid::uuid4()->toString(),
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                    ]
                );
            }
        }

        $categoryIds = $this->connection->fetchFirstColumn('SELECT id FROM catalog_categories ORDER BY name ASC');
        if ($categoryIds === []) {
            throw new \RuntimeException('No categories are available for demo products.');
        }

        return array_map(static fn (mixed $id): string => (string) $id, $categoryIds);
    }

    private function nextFakeSkuOffset(): int
    {
        $maxSku = $this->connection->fetchOne(
            "SELECT MAX(sku) FROM catalog_products WHERE sku LIKE 'FAKE%'"
        );

        if (!is_string($maxSku) || !preg_match('/^FAKE(\d+)$/', $maxSku, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }

    /**
     * @param string[] $categoryIds
     * @return array{
     *     id: string,
     *     category_id: string,
     *     name: string,
     *     description: string,
     *     sku: string,
     *     price_amount: int,
     *     price_currency: string,
     *     initial_stock: int,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    private function fakeProduct(int $number, array $categoryIds, string $now, Generator $faker): array
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'category_id' => (string) $faker->randomElement($categoryIds),
            'name' => sprintf('%s %05d', ucwords($faker->words(3, true)), $number),
            'description' => $faker->sentence(12),
            'sku' => sprintf('FAKE%08d', $number),
            'price_amount' => $faker->numberBetween(500, 250000),
            'price_currency' => 'KZT',
            'initial_stock' => $faker->numberBetween(0, 500),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param list<array{
     *     id: string,
     *     category_id: string,
     *     name: string,
     *     description: string,
     *     sku: string,
     *     price_amount: int,
     *     price_currency: string,
     *     initial_stock: int,
     *     created_at: string,
     *     updated_at: string
     * }> $products
     */
    private function insertProducts(array $products): void
    {
        if ($products === []) {
            return;
        }

        $productRows = [];
        $productParams = [];
        $stockRows = [];
        $stockParams = [];

        foreach ($products as $index => $product) {
            $productRows[] = sprintf(
                '(:id%d, :category_id%d, :name%d, :description%d, :sku%d, :price_amount%d, :price_currency%d, :created_at%d, :updated_at%d)',
                $index,
                $index,
                $index,
                $index,
                $index,
                $index,
                $index,
                $index,
                $index
            );
            $stockRows[] = sprintf('(:stock_product_id%d, :stock_quantity%d, :stock_updated_at%d)', $index, $index, $index);

            $productParams['id' . $index] = $product['id'];
            $productParams['category_id' . $index] = $product['category_id'];
            $productParams['name' . $index] = $product['name'];
            $productParams['description' . $index] = $product['description'];
            $productParams['sku' . $index] = $product['sku'];
            $productParams['price_amount' . $index] = $product['price_amount'];
            $productParams['price_currency' . $index] = $product['price_currency'];
            $productParams['created_at' . $index] = $product['created_at'];
            $productParams['updated_at' . $index] = $product['updated_at'];

            $stockParams['stock_product_id' . $index] = $product['id'];
            $stockParams['stock_quantity' . $index] = $product['initial_stock'];
            $stockParams['stock_updated_at' . $index] = $product['updated_at'];
        }

        $this->connection->executeStatement(
            'INSERT INTO catalog_products (id, category_id, name, description, sku, price_amount, price_currency, created_at, updated_at) VALUES '
            . implode(', ', $productRows),
            $productParams
        );

        $this->connection->executeStatement(
            'INSERT INTO inventory_stock (product_id, quantity, updated_at) VALUES '
            . implode(', ', $stockRows)
            . ' ON CONFLICT (product_id) DO NOTHING',
            $stockParams
        );
    }

    private function positiveIntOption(InputInterface $input, string $name): ?int
    {
        $value = $input->getOption($name);
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $filtered = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return is_int($filtered) ? $filtered : null;
    }
}
