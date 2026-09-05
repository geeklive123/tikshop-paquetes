<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('images/tikshop-logo.webp');
        $this->assertFileExists(public_path('images/tikshop-logo.webp'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    /** @return array<string, array{string, string, array<string, string>}> */
    public static function sensitiveAuthenticatedRoutes(): array
    {
        return [
            'dashboard' => ['GET', '/dashboard', []],
            'profile' => ['GET', '/profile', []],
            'password' => ['PUT', '/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]],
            'verification' => ['GET', '/verify-email', []],
            'users' => ['GET', '/users', []],
            'packages' => ['GET', '/packages', []],
            'pickup' => ['GET', '/pickup', []],
        ];
    }

    /** @param array<string, string> $parameters */
    #[DataProvider('sensitiveAuthenticatedRoutes')]
    public function test_inactive_authenticated_users_cannot_use_sensitive_routes(
        string $method,
        string $uri,
        array $parameters,
    ): void {
        $user = User::factory()->create(['active' => false]);

        $response = $this->actingAs($user)->call($method, $uri, $parameters);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
