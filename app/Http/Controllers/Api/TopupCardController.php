<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Models\Game\TopupTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TopupCardController extends Controller
{
    private const CARD_TYPES = ['Viettel', 'Mobifone', 'Vinaphone', 'Vietnamobile'];
    private const CARD_AMOUNTS = [10000, 20000, 50000, 100000, 200000, 500000];

    /**
     * POST /api/topup/card — User submits a scratch-card topup request.
     */
    public function submit(Request $request): JsonResponse
    {
        $account = $request->get('game_user');
        $type = $this->normalizeCardType($request->input('card_type', $request->input('type', '')));
        $amount = (int) $request->input('amount', 0);
        $seri = $this->normalizeCode($request->input('serial', $request->input('seri', '')));
        $pin = $this->normalizeCode($request->input('pin', ''));

        $error = $this->validateCardInput($type, $amount, $seri, $pin);
        if ($error) {
            return response()->json(['ok' => false, 'message' => $error], 422);
        }

        $username = strtolower((string) $account->username);

        $duplicate = DB::connection('game')->table('trans_log')
            ->where('seri', $seri)
            ->where('pin', $pin)
            ->where('type', $type)
            ->whereIn('status', [0, 1])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'ok' => false,
                'message' => 'Thẻ này đã được gửi hoặc đã xử lý trước đó.',
            ], 409);
        }

        try {
            $transId = 'web_' . Str::uuid()->toString();
            DB::connection('game')->table('trans_log')->insert([
                'username' => $username,
                'seri' => $seri,
                'pin' => $pin,
                'type' => $type,
                'amount' => $amount,
                'trans_id' => $transId,
                'status' => 0,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Đã gửi thẻ, vui lòng chờ hệ thống xử lý.',
                'trans_id' => $transId,
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupCard submit error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Không thể gửi thẻ lúc này.'], 500);
        }
    }

    /**
     * GET /api/topup/card/history — Current user's scratch-card history.
     */
    public function userHistory(Request $request): JsonResponse
    {
        $account = $request->get('game_user');

        try {
            $data = DB::connection('game')->table('trans_log')
                ->where('username', strtolower((string) $account->username))
                ->orderByDesc('created_at')
                ->limit(50)
                ->select('id', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status', 'created_at')
                ->get()
                ->map(fn($row) => [
                    'id' => (int) $row->id,
                    'serial' => $this->maskCode((string) $row->seri),
                    'pin' => $this->maskCode((string) $row->pin),
                    'card_type' => (string) $row->type,
                    'amount' => (int) $row->amount,
                    'trans_id' => (string) $row->trans_id,
                    'status' => (int) $row->status,
                    'created_at' => $row->created_at,
                ]);

            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Log::error('TopupCard user history error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Không thể tải lịch sử thẻ.'], 500);
        }
    }

    /**
     * POST /api/napgame247/callback — Placeholder callback endpoint for NapGame247.
     *
     * This endpoint intentionally does not credit accounts until the partner
     * documentation provides the exact signature fields and status mapping.
     */
    public function napgame247Callback(Request $request): JsonResponse
    {
        Log::info('NapGame247 callback received', [
            'ip' => $request->ip(),
            'payload' => $request->except(['pin', 'code', 'password']),
        ]);

        if (empty(config('services.napgame247.partner_key'))) {
            return response()->json([
                'success' => false,
                'message' => 'napgame247_not_configured',
            ], 503);
        }

        return response()->json([
            'success' => false,
            'message' => 'callback_signature_not_implemented',
        ], 501);
    }

    /**
     * POST /api/topup/log — Create trans_log
     */
    public function create(Request $request): JsonResponse
    {
        $username = strtolower(trim($request->input('username', '')));
        $seri = $this->normalizeCode($request->input('seri', ''));
        $pin = $this->normalizeCode($request->input('pin', ''));
        $type = $this->normalizeCardType($request->input('type', ''));
        $amount = (int) $request->input('amount', 0);
        $transId = trim($request->input('trans_id', ''));
        $status = (int) $request->input('status', 0);

        if (
            empty($username) ||
            $this->validateCardInput($type, $amount, $seri, $pin) ||
            empty($transId) ||
            !in_array($status, [0, 1, 2], true)
        ) {
            return response()->json(['ok' => false, 'error' => 'invalid_input'], 422);
        }

        // Check duplicate
        $exists = DB::connection('game')->table('trans_log')
            ->where('trans_id', $transId)
            ->exists();

        if ($exists) {
            return response()->json(['ok' => true, 'message' => 'already_exists']);
        }

        try {
            $id = DB::connection('game')->table('trans_log')->insertGetId([
                'username' => $username,
                'seri' => $seri,
                'pin' => $pin,
                'type' => $type,
                'amount' => $amount,
                'trans_id' => $transId,
                'status' => $status,
            ]);

            return response()->json([
                'ok' => true,
                'id' => $id,
                'trans_id' => $transId,
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupCard create error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }

    /**
     * GET /api/topup/log — Get trans_log by params
     */
    public function get(Request $request): JsonResponse
    {
        $transId = $request->query('trans_id', '');
        $seri = $request->query('seri', '');
        $pin = $request->query('pin', '');
        $type = $request->query('type', '');

        if (empty($transId) || empty($seri) || empty($pin) || empty($type)) {
            return response()->json(['ok' => false, 'error' => 'missing_params'], 422);
        }

        $trans = DB::connection('game')->table('trans_log')
            ->where('trans_id', $transId)
            ->where('seri', $seri)
            ->where('pin', $pin)
            ->where('type', $type)
            ->first();

        if (!$trans) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        return response()->json(['ok' => true, 'data' => $trans]);
    }

    /**
     * PUT /api/topup/log/{transId} — Update trans_log status
     */
    public function update(Request $request, string $transId): JsonResponse
    {
        $status = (int) $request->input('status', -1);
        $amount = (int) $request->input('amount', 0);

        if (!in_array($status, [1, 2], true)) {
            return response()->json(['ok' => false, 'error' => 'invalid_status'], 422);
        }

        if ($status === 1 && !in_array($amount, self::CARD_AMOUNTS, true)) {
            return response()->json(['ok' => false, 'error' => 'invalid_amount'], 422);
        }

        try {
            $result = DB::connection('game')->transaction(function () use ($transId, $status, $amount) {
                $trans = DB::connection('game')->table('trans_log')
                    ->where('trans_id', $transId)
                    ->where('status', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$trans) {
                    return ['ok' => false, 'error' => 'not_found'];
                }

                $account = null;
                if ($status === 1) {
                    $account = Account::where('username', $trans->username)
                        ->lockForUpdate()
                        ->first();

                    if (!$account) {
                        return ['ok' => false, 'error' => 'user_not_found'];
                    }
                }

                $updates = ['status' => $status];
                if ($amount > 0) {
                    $updates['amount'] = $amount;
                }

                DB::connection('game')->table('trans_log')
                    ->where('id', $trans->id)
                    ->update($updates);

                if ($status === 1) {
                    if (!TopupTransaction::where('trans_id', $transId)->exists()) {
                        TopupTransaction::create([
                            'trans_id' => $transId,
                            'username' => strtolower((string) $trans->username),
                            'user_id' => (int) $account->id,
                            'amount' => $amount,
                            'currency' => 'cash',
                            'source' => 'card',
                            'note' => 'Card ' . $trans->type,
                        ]);
                    }

                    Account::where('id', $account->id)->update([
                        'cash' => DB::raw('cash + ' . $amount),
                        'danap' => DB::raw('danap + ' . $amount),
                    ]);
                }

                return ['ok' => true];
            });

            if (!$result['ok']) {
                return response()->json(
                    ['ok' => false, 'error' => $result['error']],
                    404,
                );
            }

            return response()->json([
                'ok' => true,
                'trans_id' => $transId,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupCard update error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }

    /**
     * GET /api/topup/log/history/{username} — Card topup history
     */
    public function history(string $username): JsonResponse
    {
        try {
            $data = DB::connection('game')->table('trans_log')
                ->where('username', strtolower($username))
                ->orderByDesc('created_at')
                ->limit(50)
                ->select('id', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status', 'created_at')
                ->get();

            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Log::error('TopupCard history error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'database_error'], 500);
        }
    }

    private function normalizeCardType($type): ?string
    {
        $type = trim((string) $type);
        foreach (self::CARD_TYPES as $allowed) {
            if (strcasecmp($type, $allowed) === 0) {
                return $allowed;
            }
        }

        return null;
    }

    private function normalizeCode($value): string
    {
        return preg_replace('/\s+/', '', trim((string) $value));
    }

    private function validateCardInput(?string $type, int $amount, string $seri, string $pin): ?string
    {
        if (!$type || !in_array($type, self::CARD_TYPES, true)) {
            return 'Loại thẻ không hợp lệ.';
        }

        if (!in_array($amount, self::CARD_AMOUNTS, true)) {
            return 'Mệnh giá thẻ không hợp lệ.';
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $seri)) {
            return 'Số serial không hợp lệ.';
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $pin)) {
            return 'Mã thẻ không hợp lệ.';
        }

        return null;
    }

    private function maskCode(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', max(4, $length - 8)) . substr($value, -4);
    }
}
