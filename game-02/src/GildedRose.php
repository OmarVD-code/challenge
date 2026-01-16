<?php

declare(strict_types=1);

namespace GildedRose;

final class GildedRose
{
    /**
     * @param Item[] $items
     */
    public function __construct(
        private array $items
    ) {}

    public function updateQuality(): void
    {
        foreach ($this->items as $item) {
            if ($item->name === 'Sulfuras, Hand of Ragnaros') {
                continue;
            }

            if ($item->name === 'Aged Brie') {
                $this->increaseQuality($item, $item->sellIn <= 0 ? 2 : 1);
            } elseif ($item->name === 'Backstage passes to a TAFKAL80ETC concert') {
                if ($item->sellIn <= 0) {
                    $item->quality = 0;
                } else {
                    $inc = 1;
                    if ($item->sellIn <= 10) $inc++;
                    if ($item->sellIn <= 5)  $inc++;
                    $this->increaseQuality($item, $inc);
                }
            } else {
                $degrade = 1;

                if (str_contains($item->name, 'Conjured')) {
                    $degrade *= 2;
                }

                if ($item->sellIn <= 0) {
                    $degrade *= 2;
                }

                $this->decreaseQuality($item, $degrade);
            }

            $item->sellIn -= 1;
        }
    }

    private function increaseQuality(Item $item, int $amount): void
    {
        $item->quality = min(50, $item->quality + $amount);
    }

    private function decreaseQuality(Item $item, int $amount): void
    {
        $item->quality = max(0, $item->quality - $amount);
    }
}
