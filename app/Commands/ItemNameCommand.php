<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;
use App\Report;

#[CommandName('item-names')]
class ItemNameCommand extends Command
{
    public function run(): void
    {
        $lines = \file($this->env->configFile);

        foreach ($lines as $index => $line) {
            $line = \trim($line);

            if (
                !\str_starts_with($line, 'item[] = ') ||
                !\array_key_exists($index - 1, $lines)
            ) {
                continue;
            }

            if (
                \array_key_exists($index - 1, $lines) &&
                !\str_starts_with($lines[$index - 1], '; ')
            ) {
                echo '(has no item name)', \PHP_EOL;
            }

            echo $line, \PHP_EOL;
        }
    }
}
