<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireGamePlayer
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->attributes->get('game_user');

        if (! $account) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
                'message' => 'Bạn cần đăng nhập để dùng chức năng này.',
            ], 401);
        }

        $player = $account->player()->first();

        if (! $player) {
            return response()->json([
                'ok' => false,
                'error' => 'player_required',
                'message' => 'Bạn cần tạo nhân vật trong game trước khi dùng chức năng này.',
            ], 409);
        }

        $request->attributes->set('game_player', $player);

        return $next($request);
    }
}
