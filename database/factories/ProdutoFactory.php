<?php

namespace Database\Factories;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->words(3, true),
            'descricao' => $this->faker->sentence(),
            'preco' => $this->faker->randomFloat(2, 10, 500),
            'quantidade' => $this->faker->numberBetween(0, 100),
            'imagem' => $this->faker->imageUrl(),
            'status' => 'Ativo',
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Inativo',
        ]);
    }

    public function semEstoque(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantidade' => 0,
        ]);
    }
}

