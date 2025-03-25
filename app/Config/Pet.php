<?php

declare(strict_types=1);

namespace App\Config;

readonly class Pet
{
    public int $itemId;

    public function __construct(
        public int $speciesId,
        public ?int $cagedItemId = null,
    ) {
        $this->itemId = 82800;
    }
}
