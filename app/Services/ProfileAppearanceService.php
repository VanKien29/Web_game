<?php

namespace App\Services;

use App\Models\Game\Player;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProfileAppearanceService
{
    private const COSTUME_SLOT = 5;

    private const DEFAULT_BODY_PART = 57;

    private const DEFAULT_LEG_PART = 58;

    private const NAMEC_BODY_PART = 59;

    private const NAMEC_LEG_PART = 60;

    public function resolve(Player $player): array
    {
        try {
            $equippedItems = $this->decodeArray($player->items_body ?? null);
            $equippedItemIds = [
                0 => $this->itemIdFromSlot(Arr::get($equippedItems, 0)),
                1 => $this->itemIdFromSlot(Arr::get($equippedItems, 1)),
                self::COSTUME_SLOT => $this->itemIdFromSlot(
                    Arr::get($equippedItems, self::COSTUME_SLOT),
                ),
            ];
            $templates = $this->itemTemplates($equippedItemIds);
            $costume = $templates[$equippedItemIds[self::COSTUME_SLOT]] ?? null;
            $bodyItem = $templates[$equippedItemIds[0]] ?? null;
            $legItem = $templates[$equippedItemIds[1]] ?? null;

            $defaultBodyPart = (int) ($bodyItem->part ?? $this->defaultBodyPart($player));
            $defaultLegPart = (int) ($legItem->part ?? $this->defaultLegPart($player));
            $usesCostume = $costume && (int) ($costume->type ?? -1) === 5;
            $partIds = [
                'head' => $this->costumePart($costume, 'head', (int) ($player->head ?? 0)),
                'body' => $this->costumePart($costume, 'body', $defaultBodyPart),
                'leg' => $this->costumePart($costume, 'leg', $defaultLegPart),
            ];

            return [
                'mode' => $usesCostume ? 'costume' : 'default',
                'costume_id' => $usesCostume ? (int) $costume->id : null,
                'costume_name' => $usesCostume ? (string) $costume->name : null,
                'parts' => $partIds,
                'layers' => $this->idleLayers($partIds),
                'extensions' => [],
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->emptyAppearance($player);
        }
    }

    private function itemTemplates(array $itemIds): array
    {
        $ids = collect($itemIds)
            ->filter(fn (int $itemId) => $itemId >= 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::connection('game')
            ->table('item_template')
            ->selectRaw('id, NAME as name, TYPE as type, part, head, body, leg')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(fn (object $item) => (int) $item->id)
            ->all();
    }

    private function idleLayers(array $partIds): array
    {
        $parts = DB::connection('game')
            ->table('part')
            ->selectRaw('id, TYPE as type, DATA as data')
            ->whereIn('id', array_values($partIds))
            ->get()
            ->keyBy(fn (object $part) => (int) $part->id);

        $layers = [];
        foreach ([
            ['key' => 'leg', 'z_index' => 10],
            ['key' => 'body', 'z_index' => 20],
            ['key' => 'head', 'z_index' => 30],
        ] as $definition) {
            $key = $definition['key'];
            $partId = $partIds[$key];
            $part = $parts->get($partId);
            $frame = $part ? Arr::get($this->decodeArray($part->data ?? null), 0) : null;
            if (!is_array($frame) || (int) Arr::get($frame, 0, -1) < 0) {
                continue;
            }

            $iconId = (int) Arr::get($frame, 0);
            $layers[] = [
                'key' => $key,
                'part_id' => (int) $partId,
                'icon_id' => $iconId,
                'url' => "/assets/frontend/home/v1/images/x4/{$iconId}.png",
                'dx' => (int) Arr::get($frame, 1, 0),
                'dy' => (int) Arr::get($frame, 2, 0),
                'z_index' => $definition['z_index'],
            ];
        }

        return $layers;
    }

    private function itemIdFromSlot(mixed $slot): int
    {
        if (is_string($slot)) {
            $slot = $this->decodeArray($slot);
        }

        return is_array($slot) ? (int) Arr::get($slot, 0, -1) : -1;
    }

    private function costumePart(?object $costume, string $part, int $fallback): int
    {
        if (!$costume || (int) ($costume->type ?? -1) !== 5) {
            return $fallback;
        }

        $partId = (int) ($costume->{$part} ?? -1);

        return $partId >= 0 ? $partId : $fallback;
    }

    private function defaultBodyPart(Player $player): int
    {
        return (int) $player->gender === 1
            ? self::NAMEC_BODY_PART
            : self::DEFAULT_BODY_PART;
    }

    private function defaultLegPart(Player $player): int
    {
        return (int) $player->gender === 1
            ? self::NAMEC_LEG_PART
            : self::DEFAULT_LEG_PART;
    }

    private function emptyAppearance(Player $player): array
    {
        return [
            'mode' => 'default',
            'costume_id' => null,
            'costume_name' => null,
            'parts' => [
                'head' => (int) ($player->head ?? 0),
                'body' => $this->defaultBodyPart($player),
                'leg' => $this->defaultLegPart($player),
            ],
            'layers' => [],
            'extensions' => [],
        ];
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $normalized = preg_replace('/,\s*([\]\}])/', '$1', trim($value));
        $normalized = preg_replace('/([\[\{])\s*,/', '$1', $normalized ?: '');
        $normalized = preg_replace('/,\s*,/', ',', $normalized ?: '');
        $decoded = json_decode($normalized ?: '[]', true);

        return is_array($decoded) ? $decoded : [];
    }
}
