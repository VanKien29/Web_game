<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUnknownIpAlert;
use App\Models\Game\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function apiLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = mb_strtolower(trim($credentials['username']));

        $password = trim($credentials['password']);

        $admin = Account::query()
            ->where('username', $username)
            ->where('is_admin', 1)
            ->first();

        if (!$admin || $password === '' || trim((string) $admin->password) !== $password) {
            return response()->json([
                'ok' => false,
                'message' => 'Sai tài khoản hoặc mật khẩu, hoặc không có quyền admin.',
            ], 422);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        $this->checkIpAndAlert($request);

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => Auth::guard('admin')->id(),
                'username' => Auth::guard('admin')->user()->username,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
        ]);
    }

    public function apiLogout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    // ── Helpers ──

    private function checkIpAndAlert(Request $request): void
    {
        $admin = Auth::guard('admin')->user();
        $currentIp = $this->clientIpForAlert($request);
        $savedIp = $admin->ip_address;

        if (!$savedIp || $savedIp !== $currentIp) {
            $alertEmail = config('services.admin_alert.email') ?: $admin->email;
            if ($alertEmail) {
                try {
                    Mail::to($alertEmail)->send(new AdminUnknownIpAlert($admin, $currentIp, $savedIp));
                } catch (\Exception $e) {
                    Log::warning('Không thể gửi email cảnh báo IP admin: ' . $e->getMessage());
                }
            } else {
                Log::warning('Khong co email nhan canh bao IP admin.', [
                    'admin_id' => $admin->id,
                    'username' => $admin->username,
                    'current_ip' => $currentIp,
                    'saved_ip' => $savedIp,
                ]);
            }
        }

        $admin->ip_address = $currentIp;
        $admin->save();
    }

    private function clientIpForAlert(Request $request): string
    {
        $candidates = [
            $request->headers->get('CF-Pseudo-IPv4'),
            $request->headers->get('CF-Connecting-IP'),
            $request->headers->get('X-Real-IP'),
            $request->headers->get('X-Forwarded-For'),
            $request->ip(),
        ];

        $fallback = $request->ip();

        foreach ($candidates as $candidate) {
            foreach (explode(',', (string) $candidate) as $ip) {
                $ip = trim($ip);

                if ($ip === '') {
                    continue;
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }

                if ($fallback === null || !filter_var($fallback, FILTER_VALIDATE_IP)) {
                    $fallback = $ip;
                }
            }
        }

        return (string) $fallback;
    }
}
