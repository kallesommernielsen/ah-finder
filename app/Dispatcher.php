<?php

declare(strict_types=1);

namespace App;

use App\Config\Environment;

class Dispatcher
{
    /**
     * @var array<array{0: class-string<Command>, 1: string}>
     */
    protected array $commands = [];

    public function __construct(
        string $directory,
    ) {
        $this->mountDirectory($directory);
    }

    public function mountDirectory(string $directory): void
    {
        foreach (glob($directory . '/*Command.php') as $commandFile) {
            /** @var class-string $commandClass */
            $commandClass = '\App\Commands\\' . \str_replace(
                [
                    $directory . '/',
                    '.php',
                ],
                '',
                $commandFile,
            );

            try {
                $class = new \ReflectionClass($commandClass);

                if (
                    $class->getParentClass() === false ||
                    $class->getParentClass()->getName() !== Command::class ||
                    $class->isAbstract() ||
                    $class->isInterface()
                ) {
                    continue;
                }

                $commandName = $class->getAttributes(CommandName::class);

                if (\sizeof($commandName) !== 1) {
                    continue;
                }

                $this->mount(
                    className: $commandClass,
                    commandName: $commandName[0]->newInstance()->name,
                );
            } catch (\ReflectionException) {
                continue;
            }
        }
    }

    /**
     * @param class-string<Command> $className
     */
    protected function mount(
        string $className,
        string $commandName,
    ): void {
        $this->commands[] = [
            $className,
            $commandName,
        ];
    }

    public function dispatch(
        string $command,
        Environment $env,
    ): void {
        foreach ($this->commands as [$className, $commandName]) {
            if ($commandName !== $command) {
                continue;
            }

            (new $className($env))->run();

            return;
        }

        throw new \UnexpectedValueException('Invalid command');
    }
}
