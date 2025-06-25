<?php

declare(strict_types=1);

namespace App\Config;

readonly class Pet
{
    public int $itemId;
    public string $hash;

    public function __construct(
        public int $speciesId,
        public array $tags,
        public ?int $cagedItemId = null,
    ) {
        $this->itemId = 82800;

        if ($this->cagedItemId !== null) {
            $this->hash = 'p:'  . ((string) $this->speciesId) . ':' . ((string) $this->cagedItemId);
        } else {
            $this->hash = 'p:' . ((string) $this->speciesId);
        }
    }
}
