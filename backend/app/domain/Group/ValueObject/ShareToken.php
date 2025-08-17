<?php

declare(strict_types=1);

namespace App\domain\Group\ValueObject;

use App\domain\Shared\ValueObject\Token;

class ShareToken extends Token
{
    public static function generate(): self
    {
        $token = parent::generate();
        return new self($token->value());
    }

    public function toArray(): array
    {
        return ['share_token' => $this->value];
    }
}