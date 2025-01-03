<?php

declare(strict_types=1);

namespace Blizzard\WorldOfWarcraft;

enum Locale
{
    // CN - China
    case ZH_CN;

    // EU - Europe
    case EN_GB;
    case ES_ES;
    case FR_FR;
    case RU_RU;
    case DE_DE;
    case PT_PT;
    case IT_IT;

    // KR - Korea
    case KO_KR;

    // TW - Taiwan
    case ZH_TW;

    // US - North America
    case EN_US;
    case ES_MX;
    case PT_BR;

    public function format(): string
    {
        [$language, $country] = \explode('_', $this->name);

        return \sprintf(
            '%s_%s',
            \strtolower($language),
            $country,
        );
    }
}
