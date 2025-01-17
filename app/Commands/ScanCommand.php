<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;
use App\Config\Item;
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

        $notFoundItems = $this->env->getNotCachedItems();

        $report = new Report(
            realmMap: $this->getRealmMap(),
            // @todo Support Pets here
            notFoundItems: \array_map(
                fn(Item $item): array => [$item, $this->getItemTags($item)],
                $notFoundItems,
            ),
        );

        $totalPrice = 0;

        foreach ($cheapestList as [$item, $price, $connectedRealmId]) {
            $totalPrice += $price;

            $report->addItem(
                item: $item,
                price: $price,
                connectedRealmId: $connectedRealmId,
                tags: $this->getItemTags($item),
            );
        }

        $this->write(
            \sprintf(
                'Total price: %s for %d item%s (%d item%s not found)',
                $this->formatCurrency($totalPrice),
                \sizeof($this->env->itemList),
                \sizeof($this->env->itemList) > 1
                    ? 's'
                    : '',
                \sizeof($notFoundItems),
                \sizeof($this->env->itemList) > 1
                    ? 's'
                    : '',
            ),
        );

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

    // @todo Support pets here
    protected function getItemTags(Item $item): array
    {
        $tags = [];

        foreach (\array_keys($this->env->itemListTags) as $tag) {
            $bonusId = null;
            $tagName = $tag;

            if (\str_contains($tag, ':')) {
                [$tagName, $bonusId] = \explode(':', $tag, 2);
                $bonusId = (int) $bonusId;
            }

            if (\in_array($item->itemId, $this->env->itemListTags[$tag])) {
                if ($bonusId !== null && !\in_array($bonusId, $item->bonusIds)) {
                    continue;
                }

                $tags[] = $tagName;
            }
        }

        return $tags;
    }

    protected function formatCurrency(int $amount): string
    {
        return \sprintf(
            '%dg %ds',
            \substr((string) $amount, 0, -4),
            \substr((string) $amount, -4, 2),
        );
    }
}
