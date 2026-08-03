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
            'icon_id' => 'nullable|integer|min:0',
            'icon_x4' => 'nullable|file|mimes:png|max:2048',
        ]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
