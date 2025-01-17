<?php

declare(strict_types=1);

namespace App;

use App\Config\Item;

class Report
{
    protected array $items = [];

    public function __construct(
        protected readonly array $realmMap,
        protected readonly array $notFoundItems = [],
    ) {
    }

    protected function formatCurrencyWithIcons(int $amount): string
    {
        return \sprintf(
            '%d <img src="https://wow.zamimg.com/images/icons/money-gold.gif"> ' .
            '%d <img src="https://wow.zamimg.com/images/icons/money-silver.gif">',
            \substr((string) $amount, 0, -4),
            \substr((string) $amount, -4, 2),
        );
    }

    protected function getAvailableRealm(int $connectedRealmId): string
    {
        return \join(', ', $this->realmMap[$connectedRealmId]);
    }

    // @todo Support pets here
    public function addItem(
        Item $item,
        int $price,
        int $connectedRealmId,
        array $tags,
    ): void {
        $this->items[] = [
            $item,
            $price,
            $connectedRealmId,
            $tags,
        ];
    }

    protected function getStatTags(): \Generator
    {
        $totalPrice = 0;

        foreach ($this->items as $item) {
            $totalPrice += $item[1];
        }

        yield $this->formatCurrencyWithIcons($totalPrice);

        yield \sprintf(
            '%d items',
            \sizeof($this->items),
        );

        yield \sprintf(
            '%d item%s not found',
            \sizeof($this->notFoundItems),
            \sizeof($this->notFoundItems) > 1
                ? 's'
                : '',
        );
    }

    protected function generateHeader(): string
    {
        $html = '<!DOCTYPE html>' . \PHP_EOL;
        $html .= '<html lang="en" data-bs-theme="dark">' . \PHP_EOL;
        $html .= '<head>' . \PHP_EOL;
        $html .= '<title>Auction House Dump</title>' . \PHP_EOL;
        $html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">' . \PHP_EOL;
        $html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>' . \PHP_EOL;
        $html .= '<script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>' . \PHP_EOL;
        $html .= '<script src="https://wow.zamimg.com/js/tooltips.js"></script>' . \PHP_EOL;
        $html .= '</head>' . \PHP_EOL;
        $html .= '<body style="height: 100%;">' . \PHP_EOL;
        $html .= '<h1 class="display-1 text-center">Auction House Dump</h1>' . \PHP_EOL;
        $html .= '<div class="h-100 d-flex align-items-center justify-content-center">' . \PHP_EOL;

        foreach ($this->getStatTags() as $stat) {
            $html .= \sprintf(
                '<span class="badge rounded-pill text-bg-light me-1">%s</span>%s',
                $stat,
                \PHP_EOL,
            );
        }

        $html .= '</div>' . \PHP_EOL;

        return $html;
    }

    protected function generateFooter(): string
    {
        $html = '</body>' . \PHP_EOL;
        $html .= '</html>';

        return $html;
    }

    // @todo Support pets here
    protected function getWowheadInfo(Item $item): array
    {
        $bonusIds = $item->bonusIds;

        return [
            \sprintf(
                'https://www.wowhead.com/item=%d',
                $item->itemId,
            ),
            \sprintf(
                '%s=%s',
                \sizeof($bonusIds) > 0
                    ? 'bonus'
                    : 'item',
                \sizeof($bonusIds) > 0
                        ? \join(':', $bonusIds)
                        : $item->itemId,
            ),
            $item->itemId,
        ];
    }

    public function getHTML(): string
    {
        $html = $this->generateHeader();
        $html .= '<div class="h-100 d-flex align-items-center justify-content-center">' . \PHP_EOL;
        $html .= '<table class="table table-borderless table-hover table-dark" style="max-width: 80%;">' . \PHP_EOL;
        $html .= '<tbody>' . \PHP_EOL;

        foreach ($this->items as [$item, $price, $connectedRealmId, $tags]) {
            $html .= '<tr>' . \PHP_EOL;
            $html .= '<td class="p-1">' . \PHP_EOL;

            $html .= \sprintf(
                '<a href="%s" target="_blank" data-wowhead="%s">(item #%d)</a>',
                ...$this->getWowheadInfo($item),
            );

            $html .= '</td>' . \PHP_EOL;
            $html .= '<td class="p-1" style="text-align: right;">' . \PHP_EOL;
            $html .= $this->formatCurrencyWithIcons($price);
            $html .= '</td>' . \PHP_EOL;
            $html .= '<td class="p-1">' . \PHP_EOL;
            $html .= $this->getAvailableRealm($connectedRealmId);
            $html .= '</td>' . \PHP_EOL;
            $html .= '<td class="p-1">' . \PHP_EOL;

            foreach ($tags as $tag) {
                $html .= \sprintf(
                    ' <span class="badge rounded-pill text-bg-primary">%s</span>',
                    $tag,
                );
            }

            $html .= '</td>' . \PHP_EOL;
            $html .= '</tr>' . \PHP_EOL;
        }

        foreach ($this->notFoundItems as [$item, $tags]) {
            $html .= '<tr>' . \PHP_EOL;
            $html .= '<td class="p-1" colspan="3">' . \PHP_EOL;

            $html .= \sprintf(
                '<a href="%s" target="_blank" data-wowhead="%s">(item #%d)</a>',
                ...$this->getWowheadInfo($item),
            );

            $html .= '</td>' . \PHP_EOL;
            $html .= '<td class="p-1">' . \PHP_EOL;
            $html .= '<span class="badge rounded-pill text-bg-warning">Unavailable</span>' . \PHP_EOL;


            foreach ($tags as $tag) {
                $html .= \sprintf(
                    ' <span class="badge rounded-pill text-bg-primary">%s</span>',
                    $tag,
                );
            }

            $html .= '</td>' . \PHP_EOL;
            $html .= '</tr>' . \PHP_EOL;
        }

        $html .= '</tbody>' . \PHP_EOL;
        $html .= '</table>' . \PHP_EOL;
        $html .= '</div>' . \PHP_EOL;

        return $html . $this->generateFooter();
    }
}
