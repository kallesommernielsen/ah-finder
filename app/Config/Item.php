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
        $hash = 'i:' . ((string) $this->itemId);

        if (\sizeof($this->bonusIds) > 0) {
            $hash .= ':' . \join(':', $this->bonusIds);
        }

        $this->hash = $hash;
    }
}
