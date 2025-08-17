<?php

declare(strict_types=1);

namespace App\domain\Children\ValueObject;

use App\domain\Shared\ValueObject\Uuid;

class ChildId extends Uuid
{
    public static function generate(): self
    {
        $uuid = parent::generate();
        return new self($uuid->value());
    }

    public function toArray(): array
    {
        return ['child_id' => $this->value];
    }
}