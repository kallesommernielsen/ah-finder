<?php

declare(strict_types=1);

namespace App\Config;

readonly class Pet
{
    public int $itemId = 82800;

    public function __construct(
        public int $speciesId,
    ) {
    }
}
