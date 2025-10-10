<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;

class ProdutoController extends Controller
{
    public function index()
    {
        return response()->json(Produto::all(), 200);
    }

    public function store(Request $request)
    {
        $validate=$request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric',
            'quantidade' => 'required|integer',
            'imagem' => 'nullable|string',
            'status' => 'required|in:Ativo,Inativo',
        ]);

        $produto = Produto::create($validate);
        return response()->json($produto, 201);
    }

    public function show($id)
    {
        $produto = Produto::find($id);
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }
        return response()->json($produto, 200);
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::find($id);
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        $validate=$request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'sometimes|required|numeric',
            'quantidade' => 'sometimes|required|integer',
            'imagem' => 'nullable|string',
            'status' => 'sometimes|required|in:Ativo,Inativo',
        ]);

        $produto->update($validate);
        return response()->json($produto, 200);
    }

    public function destroy($id)
    {
        $produto = Produto::find($id);
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }
        $produto->delete();
        return response()->json(['message' => 'Produto deletado com sucesso'], 200);
    }
}