<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWelfareConfigRequest;
use App\Http\Requests\Admin\UpdateWelfareConfigRequest;
use App\Services\Admin\WelfareConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WelfareConfigController extends Controller
{
    public function __construct(private readonly WelfareConfigService $configs) {}

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->configs->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->configs->get($id));
    }

    public function store(StoreWelfareConfigRequest $request): JsonResponse
    {
        return $this->jsonResult($this->configs->create($request->validated()));
    }

    public function update(UpdateWelfareConfigRequest $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->configs->update($id, $request->validated()));
    }

    public function toggle(int $id): JsonResponse
    {
        return $this->jsonResult($this->configs->toggle($id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->configs->delete($id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
