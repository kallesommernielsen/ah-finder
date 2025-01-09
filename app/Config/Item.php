<?php

declare(strict_types=1);

namespace App\Config;

readonly class Item
{
    public function __construct(
        public int $itemId,
        public ?int $bonusId = null,
    ) {
    }
}
