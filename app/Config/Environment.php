<?php

declare(strict_types=1);

namespace App\Config;

use Blizzard\WorldOfWarcraft\Client;
use Blizzard\WorldOfWarcraft\Locale;
use Blizzard\WorldOfWarcraft\Region;

class Environment
{
    public readonly Client $client;
    public readonly string $realmCacheFile;
    public readonly string $auctionHouseDirectory;
    public readonly array $realmBlacklist;
    public readonly array $realmCategoryBlacklist;
    public readonly array $itemList;

    protected array $cachedItemLookups = [];

    /**
     * @param array<mixed> $ini
     */
    private function __construct(
        Ini $ini,
    ) {
        $this->client = new Client(
            clientId: $ini->getString('client.client_id'),
            clientSecret: $ini->getString('client.client_secret'),
            region: $ini->getEnum('client.region', Region::class),
            locale: $ini->getEnum(
                'client.locale',
                Locale::class,
                static fn(string $locale): string => \str_replace(
                    '-',
                    '_',
                    \strtoupper($locale),
                ),
            ),
        );

        if (!\is_dir($ini->getString('database.auction_houses'))) {
            throw new \RuntimeException('Auction house save directory does not exists');
        }

        $this->realmCacheFile = $ini->getString('database.realms');
        $this->auctionHouseDirectory = $ini->getString('database.auction_houses');
        $this->realmBlacklist = $ini->getIntArray('database.blacklisted_realms');
        $this->realmCategoryBlacklist = $ini->getStringArray('database.blacklisted_categories');
        $this->itemList = $this->getItemList($ini);
    }

    protected function getItemList(Ini $ini): array
    {
        $items = [];

        if ($ini->hasNamespace('test')) {
            try {
                $items = $ini->getItemArray('test');

                if (\sizeof($items) > 0) {
                    return \array_values($items);
                }
            } catch (\InvalidArgumentException) {
            }
        }

        foreach ($ini->getNamespaces() as $namespace) {
            if (!\str_contains($namespace, '/')) {
                continue;
            }

            if (!\str_contains($namespace, 't3')) {
                continue;
            }

            foreach ($ini->getItemArray($namespace) as $item) {
                if (\array_key_exists($item->hash, $items)) {
                    $items[$item->hash] = $ini->mergeItems($items[$item->hash], $item);
                } else {
                    $items[$item->hash] = $item;
                }
            }
        }

        return \array_values($items);
    }

    public static function fromDirectory(string $directory): static
    {
        $inis = [];

        foreach (\glob($directory . '/*.ini') as $iniFile) {
            $inis[] = Ini::fromFile($iniFile)->directives;
        }

        return new static(
            ini: new Ini(
                directives: \array_merge(
                    ...$inis,
                ),
            ),
        );
    }

    public function hasItem(\stdClass $item): bool
    {
        foreach ($this->itemList as $index => $itemEntry) {
            if ($item->id === $itemEntry->itemId) {
                if ($itemEntry instanceof Item && $itemEntry->bonusIds) {
                    if (!\property_exists($item, 'bonus_lists') || !\is_array($item->bonus_lists)) {
                        continue;
                    }

                    foreach ($item->bonus_lists as $bonusId) {
                        if (\in_array($bonusId, $itemEntry->bonusIds)) {
                            goto found;
                        }
                    }

                    continue;
                }

                // @todo Support Pets here

                found: {
                    $this->cachedItemLookups[\spl_object_hash($item)] = $index;
                }

                return true;
            }
        }

        return false;
    }

    public function getItem(\stdClass $item): Item
    {
        $hash = \spl_object_hash($item);

        if (!\array_key_exists($hash, $this->cachedItemLookups)) {
            throw new \InvalidArgumentException('Cannot fetch item that was not cached prior');
        }

        return $this->itemList[$this->cachedItemLookups[$hash]];
    }

    public function getNotCachedItems(): array
    {
        $items = [];

        foreach (\array_keys($this->itemList) as $index) {
            if (!\in_array($index, $this->cachedItemLookups)) {
                $items[$this->itemList[$index]->hash] = $this->itemList[$index];
            }
        }

        \ksort($items);

        return \array_values($items);
    }
}
