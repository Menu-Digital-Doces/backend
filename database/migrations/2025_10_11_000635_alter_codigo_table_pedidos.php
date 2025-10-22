<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Remova o índice único antes de dropar a coluna
            // Pode usar o nome do índice...
            // $table->dropUnique('pedidos_codigo_unique');

            // ...ou deixar o Laravel resolver o nome do índice a partir das colunas:
            $table->dropUnique(['codigo']);

            // Agora sim, remova a coluna
            $table->dropColumn('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Recrie a coluna (ajuste o tipo conforme era antes)
            $table->string('codigo')->nullable();

            // Recrie a restrição única se ainda fizer sentido
            $table->unique('codigo');
        });
    }
};

