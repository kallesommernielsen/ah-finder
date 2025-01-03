<?php

declare(strict_types=1);

namespace App\Commands;

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
                    !\in_array($auction->item->id, $this->env->itemList)
                ) {
                    continue;
                }

                if (
                    \array_key_exists($auction->item->id, $cheapestList) &&
                    $auction->buyout > $cheapestList[$auction->item->id][0]
                ) {
                    continue;
                }

                $cheapestList[$auction->item->id] = [
                    $auction->buyout,
                    $auctionHouseId,
                ];
            }
        }

        \uasort($cheapestList, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        $report = new Report(
            realmMap: $this->getRealmMap(),
        );

        foreach ($cheapestList as $itemId => [$price, $connectedRealmId]) {
            $report->addItem(
                itemId: $itemId,
                price: $price,
                connectedRealmId: $connectedRealmId,
                tags: $this->getItemTags($itemId),
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
