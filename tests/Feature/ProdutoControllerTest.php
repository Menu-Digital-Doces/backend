<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function lista_produtos()
    {
        $user = User::factory()->create();
        $p1 = Produto::factory()->create(['status' => 'Ativo']);
        $p2 = Produto::factory()->create(['status' => 'Inativo']);

        $res = $this->actingAs($user)->getJson('/api/produtos');

        $res->assertStatus(200)
            ->assertJsonFragment(['id' => $p1->id])
            ->assertJsonFragment(['id' => $p2->id]);
    }

    /** @test */
    public function cria_produto_com_sucesso()
    {
        $user = User::factory()->create();

        $payload = [
            'nome' => 'Brigadeiro Gourmet',
            'descricao' => 'Teste',
            'preco' => 12.50,
            'quantidade' => 10,
            'status' => 'Ativo',
            // imagem é opcional
        ];

        $res = $this->actingAs($user)->postJson('/api/produtos', $payload);

        // Se o seu controller retorna 201, deixe 201. Se retornar 200, troque aqui.
        $res->assertStatus(201);
        $this->assertDatabaseHas('produtos', [
            'nome' => 'Brigadeiro Gourmet',
            'status' => 'Ativo',
        ]);

        // estoque inicial via factory já deve existir (afterCreating)
        $produtoId = $res->json('id') ?? Produto::first()->id;
        $this->assertDatabaseHas('estoques', ['produto_id' => $produtoId]);
    }

    /** @test */
    public function valida_campos_obrigatorios_na_criacao()
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/api/produtos', []);
        $res->assertStatus(422); // vindo do $request->validate(...)
    }

    /** @test */
    public function mostra_detalhe_de_um_produto()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create();

        $res = $this->actingAs($user)->getJson("/api/produtos/{$produto->id}");
        $res->assertStatus(200)->assertJsonFragment(['id' => $produto->id]);
    }

    /** @test */
    public function atualiza_produto()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create(['status' => 'Ativo']);

        $res = $this->actingAs($user)->putJson("/api/produtos/{$produto->id}", [
            'nome' => 'Novo Nome',
            'descricao' => 'Desc',
            'preco' => 30.00,
            'status' => 'Inativo',
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Novo Nome',
            'status' => 'Inativo',
        ]);
    }

    /** @test */
    public function deleta_produto()
    {
        $user = User::factory()->create();
        $produto = Produto::factory()->create();

        $res = $this->actingAs($user)->deleteJson("/api/produtos/{$produto->id}");
        $res->assertStatus(200)->assertJson(['message' => 'Produto deletado com sucesso']);

        $this->assertSoftDeleted('produtos', ['id' => $produto->id]);
    }
}
