<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\User;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'codigo' => 'PED-' . date('Ymd') . '-' . strtoupper($this->faker->bothify('??????')),
            'user_id' => User::factory(),
            'produto_id' => Produto::factory(),
            'quantidade' => $this->faker->numberBetween(1, 10),
            'total' => $this->faker->randomFloat(2, 10, 1000),
            'status' => 'Pendente',
        ];
    }

    public function confirmado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Confirmado',
        ]);
    }

    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Cancelado',
        ]);
    }
}

