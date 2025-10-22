<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EstoqueControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function lista_estoques()
    {
        $user = User::factory()->create();
        $p = Produto::factory()->create();
        $e = Estoque::factory()->create(['produto_id' => $p->id, 'quantidade' => 20]);

        $res = $this->actingAs($user)->getJson('/api/estoques');
        $res->assertStatus(200)->assertJsonFragment(['id' => $e->id]);
    }

    /** @test */
    public function mostra_estoque_por_id()
    {
        $user = User::factory()->create();
        $p = Produto::factory()->create();
        $e = Estoque::factory()->create(['produto_id' => $p->id, 'quantidade' => 10]);

        $res = $this->actingAs($user)->getJson("/api/estoques/{$e->id}");
        $res->assertStatus(200)->assertJsonFragment(['id' => $e->id]);
    }

    /** @test */
    public function atualiza_quantidade()
    {
        $user = User::factory()->create();
        $p = Produto::factory()->create();
        $e = Estoque::factory()->create(['produto_id' => $p->id, 'quantidade' => 10]);

        $res = $this->actingAs($user)->putJson("/api/estoques/{$e->id}", [
            'quantidade' => 35
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('estoques', ['id' => $e->id, 'quantidade' => 35]);
    }

    /** @test */
    public function deleta_estoque()
    {
        $user = User::factory()->create();
        $p = Produto::factory()->create();
        $e = Estoque::factory()->create(['produto_id' => $p->id, 'quantidade' => 5]);

        $res = $this->actingAs($user)->deleteJson("/api/estoques/{$e->id}");
        $res->assertStatus(200)->assertJson(['message' => 'Estoque deletado com sucesso']);

        $this->assertDatabaseMissing('estoques', ['id' => $e->id]);
    }
}
