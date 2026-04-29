<?php

namespace Tests\EduTime\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_returns_token_and_user(): void
    {
        User::create([
            'name' => 'student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        $res = $this->postJson('/api/login', [
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure([
                'message', 'token',
                'user' => ['id', 'name', 'email', 'role']
            ]);
    }

    public function test_login_wrong_password_returns_401(): void
    {
        User::create([
            'name' => 'student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        $res = $this->postJson('/api/login', [
            'email' => 'student@example.com',
            'password' => 'wrong',
        ]);

        $res->assertStatus(401);
    }

    public function test_profile_requires_auth(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }
}