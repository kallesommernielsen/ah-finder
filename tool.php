<?php

declare(strict_types=1);

namespace {
    use App\Commands\Dispatcher;
    use App\Config\Environment;

    require 'vendor/autoload.php';

    (new Dispatcher(__DIR__ . '/app/Commands'))
        ->dispatch(
            $argv[1] ?? 'scan',
            Environment::fromFile(__DIR__ . '/config/app.ini'),
        );
}
