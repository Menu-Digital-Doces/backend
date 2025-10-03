<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use Nette\Utils\Random;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $pedidos = Pedido::with(['user', 'itens'])->get();
        return response()->json($pedidos, 200);
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'codigo' => 'required|string|max:255|unique:pedidos',
            'user_id' => 'required|exists:users,id',
            'produto_id' => 'required|exists:produtos,id',
            'total' => 'required|numeric',
            'quantidade' => 'required|integer',
            'status' => 'required|in:Pendente,Processando,Concluído,Cancelado',
        ]);

        $pedido = Pedido::create([
            'user_id' => auth()->id(),
            'status' => 'Pendente',
            'total' => 0
        ]);

        $total = 0;

        foreach ($validate['itens'] as $item) {
            $produto = Produto::find($item['produto_id']);
            $valoUnitario = $produto->preco;
            $subtotal = $valoUnitario * $item['quantidade'];
            $total += $subtotal;
            $pedido::create ([
                'codigo' => Random::generate(10),
                'product_id' => $produto->id,
                'quantidade' => $item['quantidade'],
                'total' => $total,
            ]);
        }
        $pedido->update(['total' => $total]);

        return response()->json($pedido->load('itens.produto'), 201);
    }

    public function show($id) {
        $pedido = Pedido::with('itens.produto')->find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        return response()->json($pedido, 200);
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
