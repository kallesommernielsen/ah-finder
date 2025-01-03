<?php

declare(strict_types=1);

namespace App;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class CommandName
{
    public function __construct(
        public string $name,
    ) {
    }
}
