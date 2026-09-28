<?php

namespace App\Core\Extensions\Navigation;

/**
 * Lazily evaluates public navigation contributions (#282).
 */
final class PublicNavigationRegistry
{
    /** @var list<\Closure(): list<PublicNavigationItem>> */
    private array $contributors = [];

    public function contribute(\Closure $contributor): void
    {
        $this->contributors[] = $contributor;
    }

    /**
     * @return list<PublicNavigationItem>
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
            static fn (PublicNavigationItem $a, PublicNavigationItem $b): int => $a->order <=> $b->order,
        );

        return $items;
    }
}
