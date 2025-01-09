<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;
use App\Report;

#[CommandName('scan')]
class ScanCommand extends Command
{
    public function run(): void
    {
        $cheapestList = [];

        foreach ($this->getAuctionHouses() as $auctionHouse) {
            $auctionHouseId = $this->extractConnectedRealmId($auctionHouse->connected_realm->href);

            $this->write(
                \sprintf(
                    '#%d',
                    $auctionHouseId,
                ),
            );

            foreach ($auctionHouse->auctions as $auction) {
                if (
                    !\property_exists($auction, 'item') ||
                    !$auction->item instanceof \stdClass ||
                    !$this->env->hasItem($auction->item) ||
                    !\property_exists($auction, 'buyout')
                ) {
                    continue;
                }

                $item = $this->env->getItem($auction->item);
                $itemHash = \spl_object_hash($item);

                if (
                    \array_key_exists($itemHash, $cheapestList) &&
                    $auction->buyout > $cheapestList[$itemHash][1]
                ) {
                    continue;
                }

                $cheapestList[$itemHash] = [
                    $item,
                    $auction->buyout,
                    $auctionHouseId,
                ];
            }
        }

        \uasort($cheapestList, static fn(array $a, array $b): int => $a[1] <=> $b[1]);

        $report = new Report(
            realmMap: $this->getRealmMap(),
        );

        foreach ($cheapestList as [$item, $price, $connectedRealmId]) {
            $report->addItem(
                item: $item,
                price: $price,
                connectedRealmId: $connectedRealmId,
                tags: $this->getItemTags($item->itemId),
            );
        }

        \file_put_contents(\getcwd() . '/report.html', $report->getHTML());
    }

    protected function getRealmMap(): array
    {
        $map = [];
        $realms = \json_decode(
            json: \file_get_contents($this->env->realmCacheFile),
            flags: \JSON_THROW_ON_ERROR,
        );

        foreach ($realms as $realm) {
            $map[$realm->id] = $realm->slugs;
        }

        return $map;
    }

    protected function getAuctionHouses(): \Generator
    {
        foreach (\glob($this->env->auctionHouseDirectory . '/*.json') as $auctionHouse) {
            $auctionHouse = \json_decode(
                json: \file_get_contents($auctionHouse),
                flags: \JSON_THROW_ON_ERROR,
            );

            if (!$auctionHouse instanceof \stdClass) {
                throw new \UnexpectedValueException('Corrupt auction house file');
            }

            yield $auctionHouse;
        }
    }

    protected function getItemTags(int $itemId): array
    {
        $tags = [];

        foreach (\array_keys($this->env->itemListTags) as $tag) {
            if (\in_array($itemId, $this->env->itemListTags[$tag])) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
