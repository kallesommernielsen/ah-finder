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
    public readonly array $itemList;
    public readonly array $itemListTags;

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
        [$this->itemList, $this->itemListTags] = $this->getItemList($ini);
    }

    protected function getItemList(Ini $ini): array
    {
        $items = [];
        $tags = [];

        foreach ($ini->getNamespaces() as $namespace) {
            try {
                $items = \array_merge(
                    $items,
                    $ini->getIntArray($namespace . '.item'),
                );

                foreach (\explode('/', $namespace) as $nsTag) {
                    $tags[$nsTag] ??= [];

                    \array_push($tags[$nsTag], ...$ini->getIntArray($namespace . '.item'));
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return [
            $items,
            $tags,
        ];
    }

    public static function fromFile(string $fileName): static
    {
        return new static(
            ini: Ini::fromFile($fileName),
        );
    }
}
