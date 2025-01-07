<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;

#[CommandName('sync-ah')]
class AuctionHouseSyncCommand extends Command
{
    public function run(): void
    {
        $this->env->client->getBatchedAuctions(
            realms: $this->loadRealms(),
            startCallback: fn(\stdClass $realm) => $this->write(
                output: $this->getRealmSlug($realm),
            ),
            endCallback: fn (\stdClass $realm, string $auctionHouse) => $this->saveAuctionHouse(
                realm: $realm,
                auctionHouse: $auctionHouse,
            )
        );
    }

    protected function loadRealms(): array
    {
        return \json_decode(
            json: \file_get_contents($this->env->realmCacheFile),
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    protected function saveAuctionHouse(
        \stdClass $realm,
        string $auctionHouse,
    ): void {
        \file_put_contents(
            filename: \sprintf(
                '%s/%s.json',
                $this->env->auctionHouseDirectory,
                $this->getRealmSlug($realm),
            ),
            data: $auctionHouse,
        );
    }

    protected function getRealmSlug(
        \stdClass $realm,
    ): string {
        return \sprintf(
            '%d-%s',
            $realm->id,
            \join('-', $realm->slugs),
        );
    }
}
