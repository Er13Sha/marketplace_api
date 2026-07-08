<?php
declare(strict_types=1);

namespace App\Catalog\Application\ReadModel;

use App\Catalog\Domain\Entity\Category;

final class CategoryView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Category $category): self
    {
        return new self(
            $category->getId(),
            $category->getName(),
            $category->getSlug(),
            $category->getDescription(),
            $category->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $category->getUpdatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
