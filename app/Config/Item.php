<?php

declare(strict_types=1);

namespace App\Config;

readonly class Item
{
    public string $hash;

    public function __construct(
        public int $itemId,
        public array $bonusIds,
        public array $tags,
    ) {
        $this->hash = (string) $this->itemId;

        if (\sizeof($this->bonusIds) > 1) {
            $this->hash .= ':' . \join(':', $this->bonusIds);
        }
    }
}
