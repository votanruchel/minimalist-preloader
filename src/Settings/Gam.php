<?php

declare(strict_types=1);

namespace MinimalistLoader\Settings;

use MinimalistLoader\Enum\GamEvent;

defined('ABSPATH') || exit;

final class Gam
{
    public const MAX_SLOT_IDS = 50;
    public const MAX_SLOT_ID_LENGTH = 160;

    /** @param list<string> $slotIds */
    public function __construct(
        public readonly GamEvent $event = GamEvent::SlotRenderEnded,
        public readonly array $slotIds = [],
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            event: GamEvent::coerce($raw['event'] ?? null),
            slotIds: self::slotIds($raw['slot_ids'] ?? []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'event' => $this->event->value,
            'slot_ids' => $this->slotIds,
        ];
    }

    /**
     * The textarea posts a newline-separated blob; the stored option is a list.
     *
     * @return list<string>
     */
    private static function slotIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $slotIds = [];

        foreach ($value as $slotId) {
            $slotId = (string) preg_replace('/[^A-Za-z0-9_\-:.]/', '', sanitize_text_field((string) $slotId));
            $slotId = substr(strtolower($slotId), 0, self::MAX_SLOT_ID_LENGTH);

            if ($slotId !== '') {
                $slotIds[] = $slotId;
            }
        }

        return array_slice(array_values(array_unique($slotIds)), 0, self::MAX_SLOT_IDS);
    }
}
