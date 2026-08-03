<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\HiddenOptionGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HiddenOptionGroupController extends Controller
{
    public function __construct(private readonly HiddenOptionGroupService $groups)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->groups->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->groups->get($id));
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request);

        return $this->jsonResult($this->groups->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request);

        return $this->jsonResult($this->groups->update($request, $id));
    }

    public function copy(int $id): JsonResponse
    {
        return $this->jsonResult($this->groups->copy($id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->groups->delete($id));
    }

    private function validatePayload(Request $request): void
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'roll_count' => ['required', 'integer', 'min:1', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.id' => ['required', 'integer', 'min:0'],
            'options.*.param_min' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'options.*.param_max' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
