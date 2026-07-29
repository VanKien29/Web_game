<?php

namespace App\Services;

use App\Models\TopupPaymentCode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use RuntimeException;

class TopupPaymentCodeService
{
    private const CODE_PREFIX = 'NRH';

    private const RANDOM_LENGTH = 12;

    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const MAX_GENERATION_ATTEMPTS = 8;

    public function issueForAccountId(int $accountId): string
    {
        $existingCode = TopupPaymentCode::query()
            ->where('nro_account_id', $accountId)
            ->value('code');

        if (is_string($existingCode) && $existingCode !== '') {
            return $existingCode;
        }

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            try {
                return TopupPaymentCode::query()->create([
                    'nro_account_id' => $accountId,
                    'code' => $this->generateCode(),
                ])->code;
            } catch (QueryException $exception) {
                $existingCode = TopupPaymentCode::query()
                    ->where('nro_account_id', $accountId)
                    ->value('code');

                if (is_string($existingCode) && $existingCode !== '') {
                    return $existingCode;
                }

                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Không thể cấp mã nạp tiền duy nhất.');
    }

    public function accountIdFromContent(string $content): ?int
    {
        $code = $this->extractCode($content);

        if ($code === null) {
            return null;
        }

        $accountId = TopupPaymentCode::query()
            ->where('code', $code)
            ->value('nro_account_id');

        return $accountId === null ? null : (int) $accountId;
    }

    public function extractCode(string $content): ?string
    {
        $normalized = Str::upper(
            preg_replace('/[^a-z0-9]/i', '', $content) ?? '',
        );

        $pattern = '/'.self::CODE_PREFIX.'['.self::ALPHABET.']{'.self::RANDOM_LENGTH.'}/';

        return preg_match($pattern, $normalized, $matches) === 1
            ? $matches[0]
            : null;
    }

    private function generateCode(): string
    {
        $characters = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < self::RANDOM_LENGTH; $index++) {
            $characters .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return self::CODE_PREFIX.$characters;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? ''), ['19', '23000', '23505'], true);
    }
}
