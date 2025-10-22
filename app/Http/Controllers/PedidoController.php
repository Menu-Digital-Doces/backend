<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Estoque;
use Illuminate\Support\Facades\Auth;


class PedidoController extends Controller
{
    public function testeCodigoUnico()
    {
        $codigo = 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        return response()->json(['codigo' => $codigo], 200);
    }
    
    public function index(Request $request)
    {
        // $pedidos = Pedido::with(['user', 'itens'])->get();
        $user = Auth::id();
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
        $user_id = Auth::id();

        $validated = $request->validate([
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|integer|min:1',
        ]);

        // Gerar código único
        $codigo = $this->gerarCodigoUnico();
        $pedidosCriados = [];

        // Usar transação para garantir atomicidade
        DB::beginTransaction();

        try {

            foreach ($validated['itens'] as $item) {
                $estoque = Estoque::where('produto_id', $item['produto_id'])->first();
                if (!$estoque) {
                    throw new \Exception("Estoque não encontrado para o produto.");
                }

                if ($estoque->quantidade < $item['quantidade']) {
                    throw new \Exception("Estoque insuficiente para o produto.");
                }

                $produto = Produto::findOrFail($item['produto_id']);

                // Verificar se produto está ativo
                if ($produto->status !== 'Ativo') {
                    throw new \Exception("Produto '{$produto->nome}' não está disponível.");
                }

                // Verificar estoque
                if ($produto->quantidade < $item['quantidade']) {
                    throw new \Exception("Estoque insuficiente para '{$produto->nome}'.");
                }

                // Criar pedido
                $pedido = Pedido::create([
                    'codigo' => $codigo,
                    'user_id' => $user_id,
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $item['quantidade'],
                    'total' => $produto->preco * $item['quantidade'],
                    'status' => 'Pendente',
                ]);

                // Atualizar estoque
                $produto->decrement('quantidade', $item['quantidade']);

                $pedidosCriados[] = $pedido;

                $estoqueInformacao = [
                    'quantidade' => $estoque->quantidade - $pedido->quantidade,
                ];

                $estoque->update($estoqueInformacao);
            }



            DB::commit();

            return response()->json([
                'message' => 'Pedido criado com sucesso',
                'codigo' => $codigo,
                'itens' => $pedidosCriados,
                'total_geral' => array_sum(array_map(fn($p) => $p->total, $pedidosCriados))
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao criar pedido',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function gerarCodigoUnico()
    {
        $tentativas = 0;
        $maxTentativas = 10;

        do {
            $codigo = 'PED-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $tentativas++;

            if ($tentativas >= $maxTentativas) {
                throw new \Exception('Não foi possível gerar um código único.');
            }
        } while (Pedido::where('codigo', $codigo)->exists());

        return $codigo;
    }



    public function show($id)
    {
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
            'status' => 'required|in:Pendente,Confirmado,Cancelado',
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
