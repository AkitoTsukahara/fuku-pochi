<?php

declare(strict_types=1);

namespace App\domain\ClothingCategory\ValueObject;

use App\domain\Shared\ValueObject\Uuid;

class CategoryId extends Uuid
{
    public static function generate(): self
    {
        $uuid = parent::generate();
        return new self($uuid->value());
    }

    public function toArray(): array
    {
        return ['category_id' => $this->value];
    }
}