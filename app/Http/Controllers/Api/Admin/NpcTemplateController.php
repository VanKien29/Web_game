<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\NpcTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NpcTemplateController extends Controller
{
    public function __construct(private readonly NpcTemplateService $npcs)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->npcs->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->npcs->get($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|integer|min:0|max:32767',
            'head' => 'required|integer|min:0|max:32767',
            'body' => 'required|integer|min:0|max:32767',
            'leg' => 'required|integer|min:0|max:32767',
            'head_data' => 'required|string|max:400000',
            'body_data' => 'required|string|max:400000',
            'leg_data' => 'required|string|max:400000',
            'icon_x4' => 'nullable',
            'icon_x4_payload' => 'nullable|string',
            'avatar_x4' => 'nullable',
        ]);

        return $this->jsonResult($this->npcs->update($request, $id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
