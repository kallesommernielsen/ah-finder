<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;

#[CommandName('sync-realms')]
class RealmSyncCommand extends Command
{
    public function run(): void
    {
        \file_put_contents(
            $this->env->realmCacheFile,
            $this->generateRealmsCache(),
        );

        $this->write('OK, realms cached');
    }

    protected function generateRealmsCache(): string
    {
        $realms = [];

        foreach ($this->env->client->getConnectedRealms()->connected_realms as $connectedRealmGroup) {
            $id = $this->extractConnectedRealmId($connectedRealmGroup->href);

            if (\in_array($id, $this->env->realmBlacklist)) {
                continue;
            }

            $connectedRealm = $this->env->client->getConnectedRealm($id);

            $slugs = [];

            if (
                !\property_exists($connectedRealm, 'realms') ||
                \gettype($connectedRealm->realms) === 'NULL'
            ) {
                continue;
            }

            foreach ($connectedRealm->realms as $realm) {
                if (\in_array($realm->category, $this->env->realmCategoryBlacklist)) {
                    continue;
                }

                $slugs[] = $realm->slug;
            }

            if (\sizeof($slugs) === 0) {
                continue;
            }

            $realms[] = [
                'id' => $connectedRealm->id,
                'slugs' => $slugs,
            ];
        }

        return \json_encode(
            value: $realms,
            flags: \JSON_THROW_ON_ERROR,
        );
    }
}
