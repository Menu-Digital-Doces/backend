<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use softDeletes;

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'quantidade',
        'imagem',
        'status',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
    ];
}
