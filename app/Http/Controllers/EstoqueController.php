<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estoque;
use App\Models\Produto;

class EstoqueController extends Controller
{
    public function index()
    {
        $estoques = Estoque::all();
        return response()->json($estoques, 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|integer|min:0',
        ]);

        $estoque = Estoque::create($data);

        // Se quiser manter o campo 'quantidade' da tabela produtos sincronizado:
        $produto = Produto::find($data['produto_id']);
        if ($produto) {
            $produto->update(['quantidade' => $data['quantidade']]);
        }

        // Seus testes não usam a resposta do store, mas mantive compatível
        return response()->json(['estoque' => $estoque, 'produto' => $produto ?? null], 201);
    }

    public function show($id)
    {
        $estoque = Estoque::find($id);
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        return response()->json($estoque, 200);
    }

    public function update(Request $request, $id)
    {
        $estoque = Estoque::find($id);
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $data = $request->validate([
            'quantidade' => 'required|integer|min:0',
        ]);

        // SET (não soma)
        $estoque->update(['quantidade' => $data['quantidade']]);

        return response()->json($estoque, 200);
    }

    public function destroy($id)
    {
        $estoque = Estoque::find($id);
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $estoque->delete();
        return response()->json(['message' => 'Estoque deletado com sucesso'], 200);
    }
}
