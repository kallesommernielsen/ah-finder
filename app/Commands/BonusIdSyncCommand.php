<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\CommandName;

#[CommandName('sync-bonus-ids')]
class BonusIdSyncCommand extends Command
{
    public function run(): void
    {
        \file_put_contents(
            $this->env->bonusIdCacheFile,
            $this->generateBonusIdCache(),
        );

        $this->write('OK, bonus ids cached');
    }

    protected function generateBonusIdCache(): string
    {
        $bonusIds = [];

        // @todo Download and parse https://raw.githubusercontent.com/simulationcraft/simc/refs/heads/thewarwithin/SpellDataDump/bonus_ids.txt

        return \json_encode(
            value: $bonusIds,
            flags: \JSON_THROW_ON_ERROR,
        );
    }
}
