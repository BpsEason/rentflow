<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_refresh_token_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ],
            'meta',
        ]);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.token_type', 'Bearer');

        // 確保返回了新的token
        $newToken = $response->json('data.access_token');
        $this->assertNotEquals($token, $newToken);
    }

    public function test_user_cannot_refresh_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_user_cannot_refresh_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }
}
