<?php
declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use Ramsey\Uuid\Uuid;

class Category
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private string $id;
    private string $name;
    private string $slug;
    private ?string $description;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name, string $slug, ?string $description = null)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->name = self::normalizeName($name);
        $this->slug = self::normalizeSlug($slug);
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function update(string $name, string $slug, ?string $description = null): void
    {
        $this->name = self::normalizeName($name);
        $this->slug = self::normalizeSlug($slug);
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Category name cannot be blank');
        }

        return $name;
    }

    private static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            throw new \InvalidArgumentException('Invalid category slug');
        }

        return $slug;
    }
}
