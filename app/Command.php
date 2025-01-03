<?php

declare(strict_types=1);

namespace App;

use App\Config\Environment;

abstract class Command
{
    final public function __construct(
        protected Environment $env,
    ) {
    }

    protected function write(string $output): void
    {
        echo $output, \PHP_EOL;
    }

    protected function extractConnectedRealmId(string $url): int
    {
        $pos = \strrpos($url, '/') + 1;

        return (int) \substr($url, $pos, \strpos($url, '?') - $pos);
    }

    abstract public function run(): void;
}
