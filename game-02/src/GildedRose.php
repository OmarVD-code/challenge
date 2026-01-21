<?php

declare(strict_types=1);

namespace GildedRose;

interface ItemUpdater
{
    public function update(Item $item): void;
}

final class GildedRose
{
    private array $items;

    private ItemUpdaterResolver $resolver;

    public function __construct(array $items)
    {
        $this->items = $items;
        $this->resolver = new ItemUpdaterResolver();
    }

    public function updateQuality(): void
    {
        foreach ($this->items as $item) {
            $this->resolver->resolve($item)->update($item);
        }
    }
}

final class ItemUpdaterResolver
{
    public function resolve(Item $item): ItemUpdater
    {
        return match (true) {
            $item->name === 'Sulfuras, Hand of Ragnaros' => new SulfurasUpdater(),
            $item->name === 'Aged Brie' => new AgedBrieUpdater(),
            $item->name === 'Backstage passes to a TAFKAL80ETC concert' => new BackstageUpdater(),
            str_starts_with($item->name, 'Conjured') => new ConjuredUpdater(),
            default => new DefaultUpdater(),
        };
    }
}

abstract class BaseUpdater
{
    protected function increaseQuality(Item $item, int $amount): void
    {
        $item->quality = min(50, $item->quality + $amount);
    }

    protected function decreaseQuality(Item $item, int $amount): void
    {
        $item->quality = max(0, $item->quality - $amount);
    }

    protected function decreaseSellIn(Item $item): void
    {
        $item->sellIn -= 1;
    }

    protected function isExpired(Item $item): bool
    {
        return $item->sellIn <= 0;
    }
}

final class SulfurasUpdater implements ItemUpdater
{
    public function update(Item $item): void
    {
        //
    }
}

final class AgedBrieUpdater extends BaseUpdater implements ItemUpdater
{
    public function update(Item $item): void
    {
        $this->increaseQuality($item, $this->isExpired($item) ? 2 : 1);
        $this->decreaseSellIn($item);
    }
}

final class BackstageUpdater extends BaseUpdater implements ItemUpdater
{
    public function update(Item $item): void
    {
        if ($this->isExpired($item)) {
            $item->quality = 0;
            $this->decreaseSellIn($item);
            return;
        }

        $inc = 1;
        if ($item->sellIn <= 10) $inc++;
        if ($item->sellIn <= 5)  $inc++;

        $this->increaseQuality($item, $inc);
        $this->decreaseSellIn($item);
    }
}

final class DefaultUpdater extends BaseUpdater implements ItemUpdater
{
    public function update(Item $item): void
    {
        $degrade = $this->isExpired($item) ? 2 : 1;
        $this->decreaseQuality($item, $degrade);
        $this->decreaseSellIn($item);
    }
}

final class ConjuredUpdater extends BaseUpdater implements ItemUpdater
{
    public function update(Item $item): void
    {
        $degrade = ($this->isExpired($item) ? 4 : 2);
        $this->decreaseQuality($item, $degrade);
        $this->decreaseSellIn($item);
    }
}
