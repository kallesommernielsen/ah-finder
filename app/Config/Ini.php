<?php

declare(strict_types=1);

namespace App\Config;

class Ini
{
    /**
     * @param array<mixed> $directives
     */
    final public function __construct(
        protected array $directives = [],
    ) {
    }

    public static function fromFile(string $file): static
    {
        return new static(
            directives: [
                ...(\parse_ini_file($file, true, \INI_SCANNER_TYPED) ?: []),
            ],
        );
    }

    public function has(string $path): bool
    {
        try {
            $this->path($path);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    public function path(string $path): mixed
    {
        $index = $this->directives;

        foreach (\explode('.', $path) as $part) {
            if (!\array_key_exists($part, $index)) {
                throw new \InvalidArgumentException('Invalid directive');
            }

            if (!\is_array($index[$part])) {
                return $index[$part];
            }

            $index = $index[$part];
        }

        if ($index === $this->directives) {
            throw new \InvalidArgumentException('Invalid directive');
        }

        return $index;
    }

    public function getScalar(string $path): int|string
    {
        $value = $this->path($path);

        if (!\is_string($value) && !\is_int($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting int|string');
        }

        return $value;
    }

    public function getNamespaces(): array
    {
        return \array_keys($this->directives);
    }

    public function getInt(string $path): int
    {
        $value = $this->path($path);

        if (!\is_int($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting int');
        }

        return $value;
    }

    public function getIntArray(string $path): array
    {
        $value = $this->path($path);

        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting int-array');
        }

        foreach ($value as $v) {
            if (!\is_int($v)) {
                throw new \UnexpectedValueException('Unexpected value, expecting int-array');
            }
        }

        return $value;
    }

    public function getString(string $path): string
    {
        $value = $this->path($path);

        if (!\is_string($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting string');
        }

        return $value;
    }

    public function getStringArray(string $path): array
    {
        $value = $this->path($path);

        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting string-array');
        }

        foreach ($value as $v) {
            if (!\is_string($v)) {
                throw new \UnexpectedValueException('Unexpected value, expecting string-array');
            }
        }

        return $value;
    }

    public function getItemArray(string $path): array
    {
        $value = $this->path($path  . '.item');

        if (!\is_array($value)) {
            throw new \UnexpectedValueException('Unexpected value, expecting string-array');
        }

        $items = [];

        foreach ($value as $v) {
            if (!\is_string($v) && !\is_int($v)) {
                throw new \UnexpectedValueException('Unexpected value, expecting string|int-array');
            }

            $bonusIds = [];

            if (\str_contains((string) $v, ':')) {
                [$v, $bonusIds] = \explode(':', (string) $v);
                $bonusIds = \array_map(\intval(...), \explode(':', $bonusIds));
            }

            $items[] = new Item(
                itemId: (int) $v,
                bonusIds: $this->has($path . '.bonusIds')
                    ? \array_unique(
                        \array_merge(
                            $bonusIds,
                            \array_map(
                                \intval(...),
                                \explode(':', (string) $this->getScalar($path . '.bonusIds')),
                            ),
                        ),
                    )
                    : $bonusIds,
            );
        }

        if ($this->has($path . '.pet')) {
            $value = $this->path($path . '.pet');

            if (\is_array($value)) {
                foreach ($value as $v) {
                    if (!\is_string($v) && !\is_int($v)) {
                        throw new \UnexpectedValueException('Unexpected value, expecting string|int-array');
                    }

                    $items[] = new Pet(
                        speciesId: (int) $v,
                    );
                }
            }
        }

        return $items;
    }

    public function getItemIdArray(string $path, ?int $bonusId): array
    {
        $items = [];

        foreach ($this->getItemArray($path) as $item) {
            if (!$item instanceof Item) {
                continue;
            }

            if ($bonusId !== null && !\in_array($bonusId, $item->bonusIds)) {
                continue;
            }

            $items[] = $item->itemId;
        }

        return $items;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $enum
     * @return T&\UnitEnum
     */
    public function getEnum(string $path, string $enum, ?\Closure $normalizer = null): object
    {
        $value = $this->path($path);

        if (!\enum_exists($enum)) {
            throw new \UnexpectedValueException('Unexpected value, expecting enum');
        }

        if (!\is_object($value)) {
            if ($normalizer !== null) {
                $value = $normalizer($value);
            }

            try {
                foreach ($enum::cases() as $case) {
                    if ($case->name === $value) {
                        /** @var T&\UnitEnum */
                        return $case;
                    }
                }

                throw new \Exception();
            } catch (\Throwable) {
                throw new \UnexpectedValueException('Unexpected value, expecting enum');
            }
        }

        /** @var T&\UnitEnum */
        return $value;
    }
}
