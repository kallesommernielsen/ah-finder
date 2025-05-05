<?php

declare(strict_types=1);

namespace App\Config;

readonly class Pet
{
    public int $itemId;
    public string $hash;

    // @todo Fix $tags
    public function __construct(
        public int $speciesId,
        public array $tags,
        public ?int $cagedItemId = null,
    ) {
        $this->itemId = 82800;

        // @todo Fix
        $this->hash = '';
    }
}
