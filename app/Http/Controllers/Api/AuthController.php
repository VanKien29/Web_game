<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Services\GameAuthService;
use App\Services\JwtService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly GameAuthService $auth,
        private readonly JwtService $jwt,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $this->auth->findForLogin($credentials['username']);

        if (
            ! $user
            || (int) $user->ban === 1
            || ! hash_equals((string) $user->password, $credentials['password'])
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sai tài khoản hoặc mật khẩu',
            ], 401);
        }

        $player = $user->player()->first(['id', 'name']);
        $token = $this->jwt->encode([
            'sub' => $user->id,
            'username' => $user->username,
            'is_admin' => (int) $user->is_admin,
        ]);

        // Lưu IP khi đăng nhập
        $user->ip_address = $request->ip();
        $user->save();

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'is_admin' => (int) $user->is_admin,
                'has_character' => $player !== null,
                'player_name' => $player?->name,
            ],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        try {
            $account = $this->auth->register(
                $credentials['username'],
                $credentials['password'],
                (string) $request->ip(),
            );
        } catch (LockTimeoutException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Đang có yêu cầu đăng ký trùng. Vui lòng thử lại.',
            ], 409);
        }

        if (! $account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tên đăng nhập đã tồn tại.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng ký thành công',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        $request->validate([
            'new_password' => 'required|string|min:3',
        ]);

        $account->password = trim($request->input('new_password'));
        $account->save();

        return response()->json([
            'ok' => true,
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        if ((int) $account->active === 1) {
            return response()->json([
                'ok' => false,
                'error' => 'already_activated',
            ], 400);
        }

        if ((int) $account->cash < 10000) {
            return response()->json([
                'ok' => false,
                'error' => 'not_enough_cash',
                'need' => 10000,
            ], 400);
        }

        $account->decrement('cash', 10000);
        $account->update(['active' => 1]);

        return response()->json([
            'ok' => true,
            'message' => 'Kích hoạt tài khoản thành công',
        ]);
    }
}
