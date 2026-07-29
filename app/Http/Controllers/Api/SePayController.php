<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Models\Game\TopupTransaction;
use App\Models\Setting;
use App\Services\TopupPaymentCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SePayController extends Controller
{
    public function __construct(
        private readonly TopupPaymentCodeService $paymentCodes,
    ) {}

    public function cron(Request $request): JsonResponse
    {
        $cfg = config('services.sepay');
        $cronSecret = (string) ($cfg['cron_secret'] ?? '');
        $secret = $request->query('secret', '');

        if ($cronSecret === '') {
            return response()->json(['ok' => false, 'error' => 'cron_not_configured'], 503);
        }

        if (! hash_equals($cronSecret, $secret)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        if (empty($cfg['token']) || empty($cfg['api_url'])) {
            return response()->json(['ok' => false, 'error' => 'sepay_not_configured'], 503);
        }

        // Fetch transactions from SePay API
        $response = Http::withToken($cfg['token'])
            ->timeout(20)
            ->get($cfg['api_url']);

        if (! $response->ok()) {
            return response()->json(['ok' => false, 'error' => 'fetch_failed']);
        }

        $data = $response->json();
        $transactions = $data['transactions'] ?? $data['data'] ?? null;
        if (! is_array($transactions)) {
            return response()->json(['ok' => false, 'error' => 'invalid_response']);
        }

        $result = $this->processTransactions($transactions, $cfg['prefix'] ?? 'naptien');

        return response()->json(['success' => true] + $result);
    }

    public function webhook(Request $request): JsonResponse
    {
        $cfg = config('services.sepay');
        $webhookKeys = $this->configuredWebhookKeys();

        if ($webhookKeys === []) {
            return response()->json(['ok' => false, 'error' => 'webhook_not_configured'], 503);
        }

        $apiKey = $this->extractWebhookApiKey($request);
        if ($apiKey === '') {
            return response()->json(['ok' => false, 'error' => 'missing_api_key'], 401);
        }

        if (! $this->validWebhookApiKey($apiKey, $webhookKeys)) {
            Log::warning('SePay webhook invalid API key', [
                'received_length' => strlen($apiKey),
                'configured_key_count' => count($webhookKeys),
                'configured_lengths' => array_map('strlen', $webhookKeys),
            ]);

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

    private function configuredWebhookKeys(): array
    {
        $keys = [
            config('services.sepay.webhook_api_key'),
            Setting::getValue('sepay_webhook_api_key'),
        ];

        return array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $keys,
        ))));
    }

    private function extractWebhookApiKey(Request $request): string
    {
        $authHeader = trim((string) $request->header('Authorization', ''));

        if (preg_match('/^(?:ApiKey|APIkey|Apikey|Bearer)\s+(.+)$/i', $authHeader, $m)) {
            return trim($m[1], " \t\n\r\0\x0B\"'");
        }

        if ($authHeader !== '') {
            return trim($authHeader, " \t\n\r\0\x0B\"'");
        }

        foreach (['X-Api-Key', 'X-API-Key', 'Api-Key'] as $header) {
            $value = trim((string) $request->header($header, ''));
            if ($value !== '') {
                return trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        return trim((string) $request->input('api_key', ''));
    }

    private function validWebhookApiKey(string $apiKey, array $webhookKeys): bool
    {
        foreach ($webhookKeys as $webhookKey) {
            if (hash_equals($webhookKey, $apiKey)) {
                return true;
            }
        }

        return false;
    }

    private function processTransactions(array $transactions, string $prefix): array
    {
        $prefix = strtolower(trim($prefix));
        $processed = 0;
        $credited = 0;

        foreach ($transactions as $gd) {
            $desc = trim((string) ($gd['transaction_content'] ?? $gd['content'] ?? ''));
            $transferType = strtolower(trim((string) ($gd['transfer_type'] ?? $gd['transferType'] ?? '')));
            $amount = $this->parseAmount($gd['amount_in'] ?? $gd['transferAmount'] ?? 0);
            $transId = trim((string) ($gd['id'] ?? $gd['reference_number'] ?? $gd['referenceCode'] ?? ''));

            if ($transferType !== '' && $transferType !== 'in') {
                continue;
            }
            if ($amount <= 0 || ! $transId) {
                continue;
            }
            $processed++;

            $account = $this->resolvePaymentAccount($desc, $prefix);
            if (! $account) {
                continue;
            }
            $username = strtolower(trim((string) $account->username));

            // Credit directly to both cash and danap
            try {
                if (TopupTransaction::where('trans_id', $transId)->exists()) {
                    continue;
                }

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
                Log::error("SePay process error for trans {$transId}: ".$e->getMessage());
            }
        }

        return [
            'ok' => true,
            'processed' => $processed,
            'credited' => $credited,
        ];
    }

    private function resolvePaymentAccount(string $content, string $legacyPrefix): ?Account
    {
        $accountId = $this->paymentCodes->accountIdFromContent($content);

        if ($accountId !== null) {
            return Account::query()->find($accountId);
        }

        if (! config('services.sepay.legacy_username_enabled', true)) {
            return null;
        }

        if (! preg_match('/\b'.preg_quote($legacyPrefix, '/').'\s+(\S+)/i', $content, $matches)) {
            return null;
        }

        $username = strtolower(trim($matches[1]));
        $accounts = Account::query()
            ->where('username', $username)
            ->limit(2)
            ->get();

        if ($accounts->count() !== 1) {
            Log::warning('SePay legacy payment username is not unique', [
                'username' => $username,
                'match_count' => $accounts->count(),
            ]);

            return null;
        }

        return $accounts->first();
    }

    private function parseAmount($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
