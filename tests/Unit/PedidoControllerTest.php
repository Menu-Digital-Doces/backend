<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\Estoque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function deve_listar_pedidos_do_usuario_autenticado()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create();
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'produto_id' => $produto->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/pedidos');

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $pedido->id]);
    }

    /** @test */
    public function deve_criar_um_pedido_com_sucesso()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create([
            'status' => 'Ativo',
            'quantidade' => 10,
            'preco' => 50.00
        ]);

        Estoque::factory()->create([
            'produto_id' => $produto->id,
            'quantidade' => 10
        ]);

        $payload = [
            'itens' => [
                ['produto_id' => $produto->id, 'quantidade' => 2],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/pedidos', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'codigo', 'itens', 'total_geral'])
                 ->assertJson(['message' => 'Pedido criado com sucesso']);

        $this->assertDatabaseHas('pedidos', [
            'user_id' => $user->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'status' => 'Pendente'
        ]);
    }

    /** @test */
    public function nao_deve_criar_pedido_com_estoque_insuficiente()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create(['status' => 'Ativo', 'quantidade' => 2]);
        Estoque::factory()->create(['produto_id' => $produto->id, 'quantidade' => 1]);

        $payload = [
            'itens' => [
                ['produto_id' => $produto->id, 'quantidade' => 3],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/pedidos', $payload);

        $response->assertStatus(400)
                 ->assertJsonStructure(['message', 'error']);
    }

    /** @test */
    public function deve_mostrar_detalhes_de_um_pedido()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create();
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'produto_id' => $produto->id
        ]);

        $response = $this->actingAs($user)->getJson("/api/pedidos/{$pedido->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $pedido->id]);
    }

    /** @test */
    public function deve_atualizar_status_de_um_pedido()
    {
        $user = User::factory()->create();
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pendente'
        ]);

        $response = $this->actingAs($user)->putJson("/api/pedidos/{$pedido->id}", [
            'status' => 'Confirmado'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'status' => 'Confirmado']);
    }

    /** @test */
    public function deve_deletar_um_pedido()
    {
        $user = User::factory()->create();
        $pedido = Pedido::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/pedidos/{$pedido->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Pedido deletado com sucesso']);

        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
    }

    /** @test */
    public function deve_gerar_codigo_unico_valido()
    {
        $response = $this->get('/api/teste-codigo-unico'); // rota auxiliar explicada abaixo
        $response->assertStatus(200);
        $codigo = $response->json('codigo');

        $this->assertMatchesRegularExpression('/^PED-\d{8}-[A-Z0-9]{6}$/', $codigo);
    }
}
