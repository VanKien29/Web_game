<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GiftBoxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GiftBoxController extends Controller
{
    public function __construct(private readonly GiftBoxService $giftBoxes)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->giftBoxes->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftBoxes->get($id));
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request, true);

        return $this->jsonResult($this->giftBoxes->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request, false);

        return $this->jsonResult($this->giftBoxes->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftBoxes->delete($id));
    }

    private function validatePayload(Request $request, bool $creating): void
    {
        $request->validate([
            'item_id' => [$creating ? 'nullable' : 'sometimes', 'integer', 'min:0'],
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'type' => 'nullable|integer',
            'part' => 'nullable|integer|min:-1',
            'gender' => 'nullable|integer|min:0|max:3',
            'icon_id' => 'nullable|integer|min:0',
            'icon_x4' => 'nullable|file|mimes:png|max:2048',
            'active' => 'nullable|boolean',
            'is_up_to_up' => 'nullable|boolean',
            'can_trade' => 'nullable|boolean',
            'min_empty_slots' => 'nullable|integer|min:1|max:100',
            'success_message' => 'nullable|string|max:255',
            'rewards' => 'nullable',
            'rewards.*.reward_item_id' => 'nullable|integer|min:1',
            'rewards.*.quantity_min' => 'nullable|integer|min:1',
            'rewards.*.quantity_max' => 'nullable|integer|min:1',
            'rewards.*.chance_weight' => 'nullable|integer|min:1',
            'rewards.*.options' => 'nullable|array',
            'rewards.*.options.*.id' => 'nullable|integer|min:0',
            'rewards.*.options.*.param_min' => 'nullable|integer',
            'rewards.*.options.*.param_max' => 'nullable|integer',
            'rewards.*.option_groups' => 'nullable|array',
            'rewards.*.option_groups.*.name' => 'nullable|string|max:120',
            'rewards.*.option_groups.*.kind' => 'nullable|string|max:40',
            'rewards.*.option_groups.*.entries' => 'nullable|array',
            'rewards.*.option_groups.*.entries.*.label' => 'nullable|string|max:120',
            'rewards.*.option_groups.*.entries.*.hsd_value' => 'nullable|string|max:40',
            'rewards.*.option_groups.*.entries.*.chance_weight' => 'nullable|numeric|min:0',
            'rewards.*.option_groups.*.entries.*.options' => 'nullable|array',
            'rewards.*.option_groups.*.entries.*.options.*.id' => 'nullable|integer|min:0',
            'rewards.*.option_groups.*.entries.*.options.*.param_min' => 'nullable|integer',
            'rewards.*.option_groups.*.entries.*.options.*.param_max' => 'nullable|integer',
        ]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
