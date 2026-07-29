<?php

namespace App\Services;

use App\Models\Game\Account;
use Illuminate\Support\Facades\Cache;

class GameAuthService
{
    public function findForLogin(string $username): ?Account
    {
        return Account::query()
            ->where('username', $username)
            ->first();
    }

    public function register(string $username, string $password, string $ipAddress): ?Account
    {
        $lockKey = 'game-account-register:'.hash('sha256', mb_strtolower($username));

        return Cache::lock($lockKey, 10)->block(3, function () use ($username, $password, $ipAddress) {
            if (Account::query()->where('username', $username)->exists()) {
                return null;
            }

            return Account::query()->create([
                'username' => $username,
                'password' => $password,
                'active' => 0,
                'is_admin' => 0,
                'ban' => 0,
                'cash' => 100000000,
                'coin' => 0,
                'danap' => 100000000,
                'ip_address' => $ipAddress,
            ]);
        });
    }
}
