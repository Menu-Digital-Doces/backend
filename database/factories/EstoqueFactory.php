<?php

namespace Database\Factories;

use App\Models\Estoque;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstoqueFactory extends Factory
{
    protected $model = Estoque::class;

    public function definition(): array
    {
        return [
            'produto_id' => Produto::factory(),
            'quantidade' => $this->faker->numberBetween(10, 100),
        ];
    }

    public function semEstoque(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantidade' => 0,
        ]);
    }

    public function estoqueInsuficiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantidade' => $this->faker->numberBetween(1, 5),
        ]);
    }
}

