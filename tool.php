<?php

declare(strict_types=1);

namespace
{
    use App\Config\Environment;
    use App\Dispatcher;

    require 'vendor/autoload.php';

    (new Dispatcher(__DIR__ . '/app/Commands'))
        ->dispatch(
            $argv[1] ?? 'scan',
            Environment::fromDirectory(__DIR__ . '/config'),
        );
}
