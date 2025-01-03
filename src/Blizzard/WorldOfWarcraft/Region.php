<?php

declare(strict_types=1);

namespace Blizzard\WorldOfWarcraft;

enum Region
{
    case CN;
    case EU;
    case KR;
    case TW;
    case US;

    public function host(): string
    {
        return match ($this) {
            self::CN => 'https://gateway.battlenet.com.cn/',
            self::EU => 'https://eu.api.blizzard.com/',
            self::KR => 'https://kr.api.blizzard.com/',
            self::TW => 'https://tw.api.blizzard.com/',
            self::US => 'https://us.api.blizzard.com/',
        };
    }

    public function defaultLocale(): Locale
    {
        return match ($this) {
            self::CN => Locale::ZH_CN,
            self::EU => Locale::EN_GB,
            self::KR => Locale::KO_KR,
            self::TW => Locale::ZH_TW,
            self::US => Locale::EN_US,
        };
    }

    /**
     * @return Locale[]
     */
    public function locales(): array
    {
        return match ($this) {
            self::CN => [
                Locale::ZH_CN,
            ],
            self::EU => [
                Locale::EN_GB,
                Locale::ES_ES,
                Locale::FR_FR,
                Locale::RU_RU,
                Locale::DE_DE,
                Locale::PT_PT,
                Locale::IT_IT,
            ],
            self::KR => [
                Locale::KO_KR,
            ],
            self::TW => [
                Locale::ZH_TW,
            ],
            self::US => [
                Locale::EN_US,
                Locale::ES_MX,
                Locale::PT_BR,
            ],
        };
    }

    public function staticNamespace(): string
    {
        return \sprintf(
            'static-%s',
            \strtolower($this->name),
        );
    }

    public function dynamicNamespace(): string
    {
        return \sprintf(
            'dynamic-%s',
            \strtolower($this->name),
        );
    }

    public function profileNamespace(): string
    {
        return \sprintf(
            'profile-%s',
            \strtolower($this->name),
        );
    }
}
