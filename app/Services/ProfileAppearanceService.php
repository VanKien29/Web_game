<?php

namespace App\Services;

use App\Models\Game\Player;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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

            $defaultBodyPart = $this->templatePart(
                $bodyItem,
                'part',
                $this->defaultBodyPart($player),
            );
            $defaultLegPart = $this->templatePart(
                $legItem,
                'part',
                $this->defaultLegPart($player),
            );
            $usesCostume = $costume && (int) ($costume->type ?? -1) === 5;
            $fallbackPartIds = [
                'head' => (int) ($player->head ?? 0),
                'body' => $defaultBodyPart,
                'leg' => $defaultLegPart,
            ];
            $requestedPartIds = [
                'head' => $this->costumePart($costume, 'head', (int) ($player->head ?? 0)),
                'body' => $this->costumePart($costume, 'body', $defaultBodyPart),
                'leg' => $this->costumePart($costume, 'leg', $defaultLegPart),
            ];

            $layers = $this->idleLayers($requestedPartIds, $fallbackPartIds);
            $renderedPartIds = collect($layers)
                ->pluck('part_id', 'key')
                ->map(fn (mixed $partId) => (int) $partId)
                ->all();
            $usesEquipmentFallback = $usesCostume
                && collect($requestedPartIds)->contains(
                    fn (int $partId, string $key) => ($renderedPartIds[$key] ?? null) !== $partId,
                );

            return [
                'mode' => $usesCostume
                    ? ($usesEquipmentFallback ? 'equipment-fallback' : 'costume')
                    : 'default',
                'costume_id' => $usesCostume ? (int) $costume->id : null,
                'costume_name' => $usesCostume ? (string) $costume->name : null,
                'parts' => [
                    'head' => $renderedPartIds['head'] ?? $requestedPartIds['head'],
                    'body' => $renderedPartIds['body'] ?? $requestedPartIds['body'],
                    'leg' => $renderedPartIds['leg'] ?? $requestedPartIds['leg'],
                ],
                'pose' => [
                    'key' => 'idle-right',
                    'zoom' => 4,
                    'origin' => 'bottom-center',
                ],
                'layers' => $layers,
                'extensions' => [],
                'complete' => count($layers) === 3,
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

    private function idleLayers(array $partIds, array $fallbackPartIds = []): array
    {
        $parts = DB::connection('game')
            ->table('part')
            ->selectRaw('id, TYPE as type, DATA as data')
            ->whereIn(
                'id',
                collect([...array_values($partIds), ...array_values($fallbackPartIds)])
                    ->filter(fn (int $partId) => $partId >= 0)
                    ->unique()
                    ->values(),
            )
            ->get()
            ->keyBy(fn (object $part) => (int) $part->id);

        $layers = [];
        foreach ([
            [
                'key' => 'head',
                'frame' => 0,
                'anchor_x' => -13,
                'anchor_y' => -34,
                'z_index' => 0,
            ],
            [
                'key' => 'leg',
                'frame' => 1,
                'anchor_x' => -8,
                'anchor_y' => -10,
                'z_index' => 1,
            ],
            [
                'key' => 'body',
                'frame' => 1,
                'anchor_x' => -9,
                'anchor_y' => -16,
                'z_index' => 2,
            ],
        ] as $definition) {
            $key = $definition['key'];
            $partId = $partIds[$key];
            $layer = $this->layerForPart($parts, $partId, $definition);

            if (! $layer) {
                $fallbackPartId = (int) ($fallbackPartIds[$key] ?? -1);
                if ($fallbackPartId !== $partId) {
                    $layer = $this->layerForPart($parts, $fallbackPartId, $definition);
                }
            }

            if ($layer) {
                $layers[] = $layer;
            }
        }

        return $layers;
    }

    private function layerForPart(
        Collection $parts,
        int $partId,
        array $definition,
    ): ?array {
        $part = $parts->get($partId);
        $frame = $part
            ? Arr::get(
                $this->decodeArray($part->data ?? null),
                $definition['frame'],
            )
            : null;
        if (! is_array($frame) || (int) Arr::get($frame, 0, -1) < 0) {
            return null;
        }

        $iconId = (int) Arr::get($frame, 0);
        $spriteUrl = $this->spriteUrl($iconId);
        if (! $spriteUrl) {
            return null;
        }

        return [
            'key' => $definition['key'],
            'part_id' => $partId,
            'icon_id' => $iconId,
            'url' => $spriteUrl,
            'x' => $definition['anchor_x'] + (int) Arr::get($frame, 1, 0),
            'y' => $definition['anchor_y'] + (int) Arr::get($frame, 2, 0),
            'z_index' => $definition['z_index'],
        ];
    }

    private function spriteUrl(int $iconId): ?string
    {
        foreach ([
            "assets/game-icons/x4/{$iconId}.png",
            "assets/frontend/home/v1/images/x4/{$iconId}.png",
        ] as $spritePath) {
            if (is_file(public_path($spritePath))) {
                return "/{$spritePath}";
            }
        }

        return null;
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
        if (! $costume || (int) ($costume->type ?? -1) !== 5) {
            return $fallback;
        }

        $partId = (int) ($costume->{$part} ?? -1);

        return $partId >= 0 ? $partId : $fallback;
    }

    private function templatePart(?object $template, string $part, int $fallback): int
    {
        $partId = (int) ($template->{$part} ?? -1);

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
            'pose' => [
                'key' => 'idle-right',
                'zoom' => 4,
                'origin' => 'bottom-center',
            ],
            'layers' => [],
            'extensions' => [],
            'complete' => false,
        ];
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $normalized = preg_replace('/,\s*([\]\}])/', '$1', trim($value));
        $normalized = preg_replace('/([\[\{])\s*,/', '$1', $normalized ?: '');
        $normalized = preg_replace('/,\s*,/', ',', $normalized ?: '');
        $decoded = json_decode($normalized ?: '[]', true);

        return is_array($decoded) ? $decoded : [];
    }
}
