<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FlyingBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FlyingBoardController extends Controller
{
    public function __construct(private readonly FlyingBoardService $flyingBoards)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->flyingBoards->list($request));
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request, creating: true);

        return $this->jsonResult($this->flyingBoards->create($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->flyingBoards->get($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request, creating: false);

        return $this->jsonResult($this->flyingBoards->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->flyingBoards->delete($id));
    }

    private function validatePayload(Request $request, bool $creating): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'item_id' => 'nullable|integer|min:1',
            'mount_id' => 'nullable|integer|min:1|max:32767',
            'gender' => 'nullable|integer|min:0|max:3',
            'n_frame' => 'required|integer|min:1|max:200',
            'mount_0_x4' => 'nullable',
            'mount_0_x4_payload' => 'nullable|string',
            'mount_1_x4' => 'nullable',
            'mount_1_x4_payload' => 'nullable|string',
            'item_icon_x4' => 'nullable',
            'item_icon_x4_payload' => 'nullable|string',
        ];

        if (!$creating) {
            unset($rules['mount_id']);
        }

        $request->validate($rules);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
