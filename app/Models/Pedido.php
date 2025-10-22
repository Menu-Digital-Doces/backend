<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pedido extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = [
        'codigo',
        'user_id',
        'produto_id',
        'total',
        'quantidade',
        'status',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->belongsTo(Produto::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Pedido $pedido) {
            if (empty($pedido->status)) {
                $pedido->status = 'Pendente'    ;
            }


            if (empty($pedido->codigo)) {
                $pedido->codigo = 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
            }
        });
    }
}
