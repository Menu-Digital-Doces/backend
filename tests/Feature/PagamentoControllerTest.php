<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\Estoque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class PagamentoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function paga_pedido_pendente()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create(['status' => 'Ativo', 'preco' => 20.00]);
        Estoque::factory()->create(['produto_id' => $produto->id, 'quantidade' => 10]);

        $pedido = Pedido::create([
            'codigo' => 'PED-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'total' => 40.00,
            'status' => 'Pendente',
        ]);

        $payload = [
            'metodo' => 'cartao_credito',
            'valor' => 40.00,
            'numero_cartao' => '4111111111111111',
            'nome_titular' => 'Teste',
            'validade' => '12/29',
            'cvv' => '123',
        ];

        $res = $this->actingAs($user)->postJson("/api/pedidos/{$pedido->id}/pagamento", $payload);

        // Ajuste o status se seu controller retornar algo diferente (ex.: 201)
        $res->assertStatus(200);
        $this->assertDatabaseHas('pagamentos', ['pedido_id' => $pedido->id]);
    }

    /** @test */
    public function falha_quando_pedido_nao_existe()
    {
        $user = User::factory()->create();

        $payload = ['metodo' => 'cartao', 'valor' => 10.00];
        $res = $this->actingAs($user)->postJson("/api/pedidos/9999/pagamento", $payload);

        $res->assertStatus(404);
    }
}
