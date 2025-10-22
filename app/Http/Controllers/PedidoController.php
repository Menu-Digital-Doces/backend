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
        $userId = $request->user()->id;

        $data = $request->validate([
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
        ]);

        $codigo = 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));

        try {
            $payload = DB::transaction(function () use ($data, $userId, $codigo) {
                $itens = [];
                $totalGeral = 0;

                foreach ($data['itens'] as $item) {
                    $produto = Produto::lockForUpdate()->find($item['produto_id']);

                    if ($produto->status !== 'Ativo') {
                        throw new \DomainException('Produto não está Ativo');
                    }

                    // $estoque = Estoque::where('produto_id', $produto->id)->lockForUpdate()->first();
                    $estoque = Estoque::where('produto_id', $produto->id)
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();


                    if (!$estoque || $estoque->quantidade < $item['quantidade']) {
                        throw new \DomainException('Quantidade solicitada maior que estoque disponível');
                    }

                    // baixa de estoque
                    $estoque->decrement('quantidade', (int)$item['quantidade']);

                    // cria o pedido (um por item) com o mesmo código)
                    $subtotal = (float)$produto->preco * (int)$item['quantidade'];
                    Pedido::create([
                        'codigo'      => $codigo,
                        'user_id'     => $userId,
                        'produto_id'  => $produto->id,
                        'quantidade'  => (int)$item['quantidade'],
                        'total'       => $subtotal,
                        'status'      => 'Pendente',
                    ]);

                    $itens[] = [
                        'produto_id' => $produto->id,
                        'quantidade' => (int)$item['quantidade'],
                        'preco'      => (float)$produto->preco,
                        'subtotal'   => $subtotal,
                    ];
                    $totalGeral += $subtotal;
                }

                // o que o closure retorna vai ser o valor do DB::transaction(...)
                return [
                    'message'     => 'Pedido criado com sucesso',
                    'codigo'      => $codigo,
                    'itens'       => $itens,
                    'total_geral' => $totalGeral,
                ];
            });

            // sucesso: COMMIT já feito, devolve 201
            return response()->json($payload, 201);
        } catch (\DomainException $e) {
            // erro de negócio: ROLLBACK automático pela transação
            return response()->json([
                'message' => 'Estoque/Produto indisponível',
                'error'   => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            // erro inesperado
            report($e);
            return response()->json([
                'message' => 'Erro interno',
            ], 500);
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
