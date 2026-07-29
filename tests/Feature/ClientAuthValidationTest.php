<?php

namespace Tests\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClientAuthValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_login_rejects_a_sql_injection_username_before_querying_the_game_database(): void
    {
        $this->postJson('/api/auth/login', [
            'username' => "admin' OR 1=1 --",
            'password' => 'anything',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_registration_requires_a_six_to_eighteen_character_alphanumeric_username(): void
    {
        foreach (['abc12', 'abc_123', 'abcdef1234567890123'] as $username) {
            $this->postJson('/api/auth/register', [
                'username' => $username,
                'password' => 'Secure@1',
                'password_confirmation' => 'Secure@1',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('username');
        }
    }

    public function test_registration_requires_letter_number_symbol_and_confirmation(): void
    {
        foreach (['abcdef', '123456', 'abc123', 'abc 123!'] as $password) {
            $this->postJson('/api/auth/register', [
                'username' => 'validuser123',
                'password' => $password,
                'password_confirmation' => $password,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->postJson('/api/auth/register', [
            'username' => 'validuser123',
            'password' => 'Secure@1',
            'password_confirmation' => 'Different@1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_public_identity_creation_routes_require_a_game_player(): void
    {
        $protectedRoutes = [
            ['api/forum/posts', 'POST'],
            ['api/forum/posts/{post}/comments', 'POST'],
            ['api/posts/{slug}/comments', 'POST'],
        ];

        foreach ($protectedRoutes as [$uri, $method]) {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(fn ($route) => $route->uri() === $uri && in_array($method, $route->methods(), true));

            $this->assertNotNull($route, "Route {$method} {$uri} was not registered.");
            $this->assertContains('game.auth', $route->gatherMiddleware());
            $this->assertContains('game.player', $route->gatherMiddleware());
        }
    }
}
