<?php

namespace Tests\Unit;

use App\Http\Middleware\RequireGamePlayer;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequireGamePlayerTest extends TestCase
{
    public function test_account_without_player_is_rejected(): void
    {
        $account = new class
        {
            public function player(): object
            {
                return new class
                {
                    public function first(): null
                    {
                        return null;
                    }
                };
            }
        };

        $request = Request::create('/api/forum/posts', 'POST');
        $request->attributes->set('game_user', $account);

        $response = (new RequireGamePlayer)->handle(
            $request,
            fn () => response()->json(['ok' => true]),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('player_required', $response->getData(true)['error']);
    }

    public function test_account_with_player_can_continue_and_player_is_attached_to_request(): void
    {
        $player = (object) ['id' => 99, 'name' => 'ChienBinh'];
        $account = new class($player)
        {
            public function __construct(private readonly object $player) {}

            public function player(): object
            {
                return new class($this->player)
                {
                    public function __construct(private readonly object $player) {}

                    public function first(): object
                    {
                        return $this->player;
                    }
                };
            }
        };

        $request = Request::create('/api/forum/posts', 'POST');
        $request->attributes->set('game_user', $account);

        $response = (new RequireGamePlayer)->handle(
            $request,
            fn (Request $nextRequest) => response()->json([
                'player_id' => $nextRequest->attributes->get('game_player')->id,
            ]),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(99, $response->getData(true)['player_id']);
    }
}
