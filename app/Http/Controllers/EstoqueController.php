<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estoque;
use App\Models\Produto;

class EstoqueController extends Controller
{
    public function index()
    {
        // Retorna todos os estoques com seus produtos
        $estoques = Estoque::with('produto')->get();
        return response()->json($estoques, 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'produto_id' => 'required|exists:produtos,id|unique:estoques,produto_id',
            'quantidade' => 'required|integer|min:0',
        ]);

        // Verifica se o produto já tem estoque (one-to-one)
        $produtoComEstoque = Produto::with('estoque')->find($data['produto_id']);
        
        if ($produtoComEstoque && $produtoComEstoque->estoque) {
            return response()->json([
                'message' => 'Este produto já possui um estoque cadastrado'
            ], 422);
        }

        // Cria o estoque
        $estoque = Estoque::create($data);

        // Carrega a relação com o produto
        $estoque->load('produto');

        return response()->json($estoque, 201);
    }

    public function show($id)
    {
        // Busca estoque com produto relacionado
        $estoque = Estoque::with('produto')->find($id);
        
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        return response()->json($estoque, 200);
    }

    public function update(Request $request, $id)
    {
        $estoque = Estoque::with('produto')->find($id);
        
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $data = $request->validate([
            'quantidade' => 'required|integer|min:0',
        ]);

        // Atualiza a quantidade do estoque
        $estoque->update(['quantidade' => $data['quantidade']]);

        // Recarrega a relação
        $estoque->load('produto');

        return response()->json($estoque, 200);
    }

    public function destroy($id)
    {
        $estoque = Estoque::with('produto')->find($id);
        
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        // Verifica se há quantidade antes de deletar (opcional)
        if ($estoque->quantidade > 0) {
            return response()->json([
                'message' => 'Não é possível deletar estoque com quantidade disponível'
            ], 400);
        }

        $estoque->delete();
        
        return response()->json([
            'message' => 'Estoque deletado com sucesso'
        ], 200);
    }

    // Método adicional: buscar estoque por produto_id
    public function getByProduto($produto_id)
    {
        $produto = Produto::with('estoque')->find($produto_id);
        
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        if (!$produto->estoque) {
            return response()->json(['message' => 'Este produto não possui estoque'], 404);
        }

        return response()->json($produto->estoque, 200);
    }
}