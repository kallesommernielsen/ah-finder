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
    public readonly array $itemsMap;
    public readonly array $itemListTags;

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

        if (!\is_writable($ini->getString('database.realms'))) {
            throw new \RuntimeException('Realms file is not writable');
        }

        if (!\is_dir($ini->getString('database.auction_houses'))) {
            throw new \RuntimeException('Auction house save directory does not exists');
        }

        $this->realmCacheFile = $ini->getString('database.realms');
        $this->auctionHouseDirectory = $ini->getString('database.auction_houses');
        $this->realmBlacklist = $ini->getIntArray('database.blacklisted_realms');
        $this->realmCategoryBlacklist = $ini->getStringArray('database.blacklisted_categories');
        [$this->itemList, $this->itemsMap, $this->itemListTags] = $this->getItemList($ini);
    }

    protected function getItemList(Ini $ini): array
    {
        $items = [];
        $tags = [];

        foreach ($ini->getNamespaces() as $namespace) {
            if (!\str_contains($namespace, '/')) {
                continue;
            }

            $items = \array_merge(
                $items,
                $ini->getItemArray($namespace),
            );

            foreach (\explode('/', $namespace) as $nsTag) {
                $tags[$nsTag] ??= [];

                \array_push($tags[$nsTag], ...$ini->getItemIdArray($namespace));
            }
        }

        return [
            $items,
            \array_flip(
                \array_map(
                    static fn(Item $item): int => $item->itemId,
                    $items,
                ),
            ),
            $tags,
        ];
    }

    public static function fromFile(string $fileName): static
    {
        return new static(
            ini: Ini::fromFile($fileName),
        );
    }

    public function hasItem(\stdClass $item): bool
    {
        foreach ($this->itemList as $index => $itemEntry) {
            if ($item->id === $itemEntry->itemId) {

                if ($itemEntry->bonusId !== null) {
                    if (!\property_exists($item, 'bonus_lists') || !\is_array($item->bonus_lists)) {
                        continue;
                    }

                    foreach ($item->bonus_lists as $bonusId) {
                        if ($bonusId !== $itemEntry->bonusId) {
                            continue 2;
                        }
                    }
                }

                $this->cachedItemLookups[\spl_object_hash($item)] = $index;

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
}
