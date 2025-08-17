<?php

declare(strict_types=1);

namespace App\domain\Group\ValueObject;

use App\domain\Shared\ValueObject\Uuid;

class GroupId extends Uuid
{
    public static function generate(): self
    {
        $uuid = parent::generate();
        return new self($uuid->value());
    }

    public function toArray(): array
    {
        return ['group_id' => $this->value];
    }
}