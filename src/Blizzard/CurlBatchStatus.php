<?php

namespace Blizzard;

enum CurlBatchStatus
{
    case NOT_STARTED;
    case STARTED;
    case COMPLETE;
}
