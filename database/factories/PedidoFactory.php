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
            'user_id'    => User::factory(),
            'produto_id' => Produto::factory(),
            'quantidade' => $this->faker->numberBetween(1, 5),
            'status'     => 'novo',
        ];
    }
}