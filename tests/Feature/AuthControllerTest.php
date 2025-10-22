<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // garante um secret de JWT em testing e deixa o guard api com driver jwt
        config([
            'jwt.secret' => Str::random(32),
            'auth.guards.api.driver' => 'jwt',
        ]);
    }

    /** @test */
    public function registra_usuario()
    {
        $payload = [
            'name' => 'Tiago',
            'email' => 'tiago@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123', // necessário por 'confirmed'
        ];

        $res = $this->postJson('/api/register', $payload);

        // o controller retorna 201 ao registrar
        $res->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'tiago@example.com']);
    }

    /** @test */
    public function faz_login_e_retornatoken()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $res = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        // login retorna apenas 'token'
        $res->assertStatus(200)->assertJsonStructure(['token']);
        $this->assertNotEmpty($res->json('token'));
    }

    /** @test */
    public function retorna_usuario_autenticado()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // pega um token válido
        $login = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);

        $token = $login->json('token');

        // usa sessão (para passar no middleware em testing) + Bearer (caso o controller use guard('api'))
        $res = $this->actingAs($user)
                    ->withHeader('Authorization', "Bearer {$token}")
                    ->getJson('/api/user');

        $res->assertStatus(200)->assertJsonFragment(['email' => 'user@example.com']);
    }

    /** @test */
    public function faz_logout()
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // pega um token válido
        $login = $this->postJson('/api/login', [
            'email' => 'logout@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);

        $token = $login->json('token');

        // sessão + Bearer: cobre tanto fluxo por sessão quanto por JWT
        $res = $this->actingAs($user)
                    ->withHeader('Authorization', "Bearer {$token}")
                    ->postJson('/api/logout');

        $res->assertStatus(200)->assertJson(['message' => 'Logout realizado com sucesso']);
    }
}
