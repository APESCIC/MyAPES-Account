<?php

namespace App\Core\Extensions\Navigation;

final class StaffPluginNavigationRegistry
{
    /** @var list<\Closure(): list<StaffPluginNavigationItem>> */
    private array $contributors = [];

    public function contribute(\Closure $contributor): void
    {
        $this->contributors[] = $contributor;
    }

    /**
     * @return list<StaffPluginNavigationItem>
     */
    public function enabledItems(): array
    {
        $items = [];

        foreach ($this->contributors as $contributor) {
            foreach ($contributor() as $item) {
                if ($item->enabled) {
                    $items[] = $item;
                }
            }
        }

        usort(
            $items,
            static fn (StaffPluginNavigationItem $a, StaffPluginNavigationItem $b): int => $a->order <=> $b->order,
        );

        return $items;
    }
}
