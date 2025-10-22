<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use HasFactory;
    protected $guarded = []; // opcional, ajuda nos testes

    use SoftDeletes; // não exclui do banco, apenas marca a data de exclusão

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
