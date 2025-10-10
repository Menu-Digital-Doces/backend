<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use Nette\Utils\Random;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        // $pedidos = Pedido::with(['user', 'itens'])->get();
        $user = 3;
        $pedidos = DB::table('pedidos')
            ->join('users', 'pedidos.user_id', '=', 'users.id')
            ->join('produtos', 'pedidos.produto_id', '=', 'produtos.id')
            ->select('pedidos.*', 'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
            ->where('users.id', $user)
            ->get();

        return response()->json($pedidos, 200);
    }

    public function store(Request $request)
    {
        // Em uma aplicação real, o ID do usuário viria da autenticação
        $user_id = 3;

        // ALTERAÇÃO 1: Validar um array de itens
        // A validação agora espera um campo 'itens' que seja um array.
        // O '*' significa que cada elemento dentro do array 'itens' deve seguir a regra.
        $validated = $request->validate([
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|integer|min:1',
        ]);

        $codigo = Random::generate(10);
        $pedidosCriados = []; // Array para guardar os pedidos criados

        // ALTERAÇÃO 2: Iterar sobre o array 'itens' validado
        foreach ($validated['itens'] as $item) {
            $produto = Produto::find($item['produto_id']);

            // Se o produto não for encontrado, podemos pular ou retornar um erro.
            if (!$produto) {
                continue;
            }

            $pedido = new Pedido([
                'codigo' => $codigo,
                'user_id' => $user_id,
                'produto_id' => $item['produto_id'],
                'quantidade' => $item['quantidade'],
                'total' => $produto->preco * $item['quantidade'],
                'status' => 'Pendente',
            ]);

            // ALTERAÇÃO 3: Salvar a instância do pedido
            $pedido->save();
            $pedidosCriados[] = $pedido; // Adiciona o pedido salvo ao array
        }

        // ALTERAÇÃO 4: Retornar uma resposta mais útil
        if (empty($pedidosCriados)) {
            return response()->json(['message' => 'Nenhum item válido para criar o pedido.'], 400);
        }

        return response()->json($pedidosCriados, 201);
    }

    public function show($id) {
        $pedidos = DB::table('pedidos')
            ->join('users', 'pedidos.user_id', '=', 'users.id')
            ->join('produtos', 'pedidos.produto_id', '=', 'produtos.id')
            ->select('pedidos.*', 'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
            ->where('pedidos.id', $id)
            ->first();

        return response()->json($pedidos, 200);
    }

    public function update(Request $request, $id)
    {
        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }

        $validate = $request->validate([
            'status' => 'sometimes|required|in:Pendente,Processando,Concluído,Cancelado',
        ]);

        $pedido->update($validate);
        return response()->json($pedido, 200);
    }

    public function destroy($id)
    {
        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        $pedido->delete();
        return response()->json(['message' => 'Pedido deletado com sucesso'], 200);
    }
}
