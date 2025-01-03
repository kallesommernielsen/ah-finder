<?php

declare(strict_types=1);

namespace Blizzard\WorldOfWarcraft;

enum Endpoint: string
{
    // Auction House
    case AUCTIONS = 'data/wow/connected-realm/%d/auctions';

    // Connected Realms
    case CONNECTED_REALM_INDEX = 'data/wow/connected-realm/index';
    case CONNECTED_REALM = 'data/wow/connected-realm/%d';

    // Realms
    case REALMS_INDEX = 'data/wow/realm/index';
}
