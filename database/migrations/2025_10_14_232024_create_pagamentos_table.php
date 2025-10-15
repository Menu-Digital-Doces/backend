<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->integer('pedido_id');
            $table->enum('status', ['pendente', 'aprovado', 'recusado'])->default('pendente');
            $table->enum('metodo', ['cartao_credito', 'pix'])->default('cartao_credito');
            $table->decimal('valor', 10, 2);
            $table->string('numero_cartao')->nullable();
            $table->string('nome_titular')->nullable();
            $table->string('validade')->nullable();
            $table->string('cvv')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
