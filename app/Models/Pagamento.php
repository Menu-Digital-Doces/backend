<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $fillable = [
        'pedido_id',
        'status',
        'metodo',
        'valor',
        'numero_cartao',
        'nome_titular',
        'validade',
        'cvv',
    ];
}
