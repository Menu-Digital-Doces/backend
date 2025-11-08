<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Estoque;

class ProdutoController extends Controller
{
    public function index()
    {
        // Retorna todos os produtos com seus estoques
        $produtos = Produto::with('estoque')->get();
        return response()->json($produtos, 200);
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric',
            'quantidade' => 'required|integer',
            'imagem' => 'nullable|string',
            'status' => 'required|in:Ativo,Inativo',
        ]);

        // Cria o produto
        $produto = Produto::create([
            'nome' => $validate['nome'],
            'descricao' => $validate['descricao'] ?? null,
            'preco' => $validate['preco'],
            'imagem' => $validate['imagem'] ?? null,
            'status' => $validate['status'],
        ]);

        // Cria o estoque através da relação
        $produto->estoque()->create([
            'quantidade' => $validate['quantidade'],
        ]);

        // Retorna o produto com estoque
        return response()->json($produto->load('estoque'), 201);
    }

    public function show($id)
    {
        // Busca produto com estoque
        $produto = Produto::with('estoque')->find($id);
        
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }
        
        return response()->json($produto, 200);
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::with('estoque')->find($id);
        
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        $validate = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'sometimes|required|numeric',
            'quantidade' => 'sometimes|required|integer',
            'imagem' => 'nullable|string',
            'status' => 'sometimes|required|in:Ativo,Inativo',
        ]);

        // Separa os dados do produto e do estoque
        $dadosProduto = collect($validate)->except('quantidade')->toArray();
        
        // Atualiza o produto
        $produto->update($dadosProduto);

        // Atualiza o estoque se a quantidade foi enviada
        if (isset($validate['quantidade'])) {
            if ($produto->estoque) {
                $produto->estoque->update([
                    'quantidade' => $validate['quantidade']
                ]);
            } else {
                // Cria estoque caso não exista
                $produto->estoque()->create([
                    'quantidade' => $validate['quantidade']
                ]);
            }
        }

        // Recarrega a relação e retorna
        return response()->json($produto->load('estoque'), 200);
    }

    public function destroy($id)
    {
        $produto = Produto::with('estoque')->find($id);
        
        if (!$produto) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        // Verifica se há estoque disponível
        if ($produto->estoque && $produto->estoque->quantidade > 0) {
            return response()->json([
                'message' => 'Não é possível deletar o produto com estoque disponível'
            ], 400);
        }

        // Deleta o estoque (se existir) - cascade já faz isso automaticamente
        // Mas podemos fazer manualmente para garantir
        if ($produto->estoque) {
            $produto->estoque->delete();
        }

        // Deleta o produto
        $produto->delete();
        
        return response()->json([
            'message' => 'Produto deletado com sucesso'
        ], 200);
    }
}