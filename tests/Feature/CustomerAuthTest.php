<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['is_public' => true]);
    }

    public function test_registration_rolls_back_user_when_jwt_generation_throws(): void
    {
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with(Mockery::on(function (User $user): bool {
                $this->assertTrue($user->exists);
                $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new-member@example.com']);

                return true;
            }))
            ->andThrow(new JWTException('JWT generation failed.'));

        $response = $this->postJson($this->registrationUrl(), $this->registrationData());

        $response->assertStatus(500)
            ->assertExactJson([
                'status' => 'error',
                'message' => '無法建立token',
                'data' => null,
                'meta' => [],
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'new-member@example.com']);
    }

    public function test_registration_returns_user_and_token_in_existing_response_contract(): void
    {
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with(Mockery::on(fn (User $user): bool => $user->exists && $user->email === 'new-member@example.com'))
            ->andReturn('test.jwt.token');

        $response = $this->postJson($this->registrationUrl(), $this->registrationData());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', '操作成功')
            ->assertJsonPath('data.access_token', 'test.jwt.token')
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.name', 'New Member')
            ->assertJsonPath('data.user.email', 'new-member@example.com')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'token_type', 'expires_in', 'user' => ['id', 'name', 'email']],
                'meta',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New Member',
            'email' => 'new-member@example.com',
        ]);
        $this->assertGreaterThan(0, $response->json('data.expires_in'));
    }

    private function registrationUrl(): string
    {
        return "/api/v1/{$this->tenant->slug}/customer/auth/register";
    }

    private function registrationData(): array
    {
        return [
            'name' => 'New Member',
            'email' => 'new-member@example.com',
            'password' => 'password123',
            'phone' => '0912345678',
        ];
    }
}
