<?php

namespace Database\Factories;

use App\Models\Estoque;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstoqueFactory extends Factory
{
    protected $model = Estoque::class; // <- ADICIONE

    public function definition()
    {
        return [
            'produto_id' => Produto::factory(),
            'quantidade' => 20,
        ];
    }
}