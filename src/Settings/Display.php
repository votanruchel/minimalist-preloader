<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

use MinimalistLoader\Enum\Location;

defined('ABSPATH') || exit;

final class Display
{
    public const MAX_EXCLUDED_IDS = 500;

    /**
     * @param list<Location> $locations An empty list means "every supported location".
     * @param list<int>      $excludedIds
     */
    public function __construct(
        public readonly array $locations = [],
        public readonly array $excludedIds = [],
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            locations: self::locations($raw['locations'] ?? []),
            excludedIds: self::ids($raw['excluded_ids'] ?? []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'locations' => array_map(static fn (Location $l): string => $l->value, $this->locations),
            'excluded_ids' => $this->excludedIds,
        ];
    }

    public function includes(Location $location): bool
    {
        return in_array($location, $this->locations, true);
    }

    /** Whether the loader may run for the request currently being rendered. */
    public function allows(int $queriedId): bool
    {
        if ($queriedId > 0 && in_array($queriedId, $this->excludedIds, true)) {
            return false;
        }

        foreach ($this->locations ?: Location::cases() as $location) {
            if ($location->matchesCurrentRequest()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Location> */
    private static function locations(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $locations = [];

        foreach ($value as $item) {
            $location = Location::tryCoerce($item);

            if ($location !== null) {
                // Keyed by value so duplicates collapse without comparing enum instances.
                $locations[$location->value] = $location;
            }
        }

        return array_values($locations);
    }

    /** @return list<int> */
    private static function ids(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = array_filter(array_map(absint(...), $value));

        return array_slice(array_values(array_unique($ids)), 0, self::MAX_EXCLUDED_IDS);
    }
}
