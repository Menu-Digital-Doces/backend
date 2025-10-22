<?php

namespace Database\Factories;

use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'nome'  => $this->faker->words(3, true),
            'preco' => $this->faker->randomFloat(2, 10, 1000),
            // REMOVIDO 'quantidade'
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Produto $produto) {
            // garante que todo produto tenha estoque vinculado
            Estoque::factory()->create([
                'produto_id' => $produto->id,
                'quantidade' => 50, // ajuste se quiser
            ]);
        });
    }
}
