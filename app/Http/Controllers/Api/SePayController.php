<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Models\Game\TopupTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SePayController extends Controller
{
    public function cron(Request $request): JsonResponse
    {
        $cfg = config('services.sepay');
        $cronSecret = (string) ($cfg['cron_secret'] ?? '');
        $secret = $request->query('secret', '');

        if ($cronSecret === '') {
            return response()->json(['ok' => false, 'error' => 'cron_not_configured'], 503);
        }

        if (!hash_equals($cronSecret, $secret)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        if (empty($cfg['token']) || empty($cfg['api_url'])) {
            return response()->json(['ok' => false, 'error' => 'sepay_not_configured'], 503);
        }

        // Fetch transactions from SePay API
        $response = Http::withToken($cfg['token'])
            ->timeout(20)
            ->get($cfg['api_url']);

        if (!$response->ok()) {
            return response()->json(['ok' => false, 'error' => 'fetch_failed']);
        }

        $data = $response->json();
        $transactions = $data['transactions'] ?? $data['data'] ?? null;
        if (!is_array($transactions)) {
            return response()->json(['ok' => false, 'error' => 'invalid_response']);
        }

        $result = $this->processTransactions($transactions, $cfg['prefix'] ?? 'naptien');

        return response()->json(['success' => true] + $result);
    }

    public function webhook(Request $request): JsonResponse
    {
        $cfg = config('services.sepay');
        $webhookKey = (string) ($cfg['webhook_api_key'] ?? '');

        if ($webhookKey === '') {
            return response()->json(['ok' => false, 'error' => 'webhook_not_configured'], 503);
        }

        // Verify API Key
        $authHeader = $request->header('Authorization', '');
        if (!preg_match('/^APIkey\s+(\S+)$/i', $authHeader, $m)) {
            return response()->json(['ok' => false, 'error' => 'missing_api_key'], 401);
        }

        if (!hash_equals($webhookKey, $m[1])) {
            return response()->json(['ok' => false, 'error' => 'invalid_api_key'], 403);
        }

        $data = $request->all();

        if (isset($data['transactions'])) {
            $transactions = $data['transactions'];
        } elseif (isset($data['id'])) {
            $transactions = [$data];
        } else {
            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 400);
        }

        $result = $this->processTransactions($transactions, $cfg['prefix'] ?? 'naptien');

        return response()->json($result);
    }

    private function processTransactions(array $transactions, string $prefix): array
    {
        $prefix = strtolower(trim($prefix));
        $processed = 0;
        $credited = 0;

        foreach ($transactions as $gd) {
            $desc = strtolower(trim($gd['transaction_content'] ?? $gd['content'] ?? ''));
            $transferType = strtolower(trim((string) ($gd['transfer_type'] ?? $gd['transferType'] ?? '')));
            $amount = $this->parseAmount($gd['amount_in'] ?? $gd['transferAmount'] ?? 0);
            $transId = trim((string) ($gd['id'] ?? $gd['reference_number'] ?? $gd['referenceCode'] ?? ''));

            if ($transferType !== '' && $transferType !== 'in') continue;
            if ($amount <= 0 || !$transId) continue;
            $processed++;

            if (!preg_match('/\b' . preg_quote($prefix, '/') . '\s+(\S+)/i', $desc, $m)) {
                continue;
            }

            $username = strtolower(trim($m[1]));

            // Credit directly to both cash and danap
            try {
                if (TopupTransaction::where('trans_id', $transId)->exists()) {
                    continue;
                }

                $account = Account::where('username', $username)->first();
                if (!$account) continue;

                DB::connection('game')->transaction(function () use ($account, $transId, $username, $amount) {
                    TopupTransaction::create([
                        'trans_id' => $transId,
                        'username' => $username,
                        'user_id' => $account->id,
                        'amount' => $amount,
                        'currency' => 'cash',
                        'source' => 'sepay',
                        'note' => 'SePay ATM',
                    ]);

                    $account->increment('cash', $amount);
                    $account->increment('danap', $amount);
                });

                $credited++;
            } catch (\Throwable $e) {
                Log::error("SePay process error for trans {$transId}: " . $e->getMessage());
            }
        }

        return [
            'ok' => true,
            'processed' => $processed,
            'credited' => $credited,
        ];
    }

    private function parseAmount($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
