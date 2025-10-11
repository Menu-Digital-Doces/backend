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
        $validate = $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|integer|min:0',
        ]);

        $produto = Produto::find($validate['produto_id']);
        $estoque = Estoque::create($validate);
        $produto->quantidade = $validate['quantidade'];

        return response()->json(['estoque' => $estoque, 'produto' => $produto], 201);
    }

    public function show($id)
    {
        $estoque = Estoque::find($id);

        return response()->json($estoque, 200);
    }

    function update(Request $request, $id)
    {
        $estoque = Estoque::where('produto_id', $id)->first();
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $validate = $request->validate([
            'quantidade' => 'sometimes|required|integer|min:0',
        ]);

        $incremento = [
            'quantidade' => $validate['quantidade'] + $estoque->quantidade,
        ];
        $estoque->update($incremento);

        return response()->json($estoque, 200);
    }

    public function destroy($id)
    {
        $estoque = Estoque::where('produto_id', $id)->first();
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $estoque->delete();
        return response()->json(['message' => 'Estoque deletado com sucesso'], 200);
    }

}
