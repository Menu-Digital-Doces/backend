<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagamento;
use App\Models\Pedido;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PagamentoController extends Controller
{
    public function Pagar(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'metodo' => 'required|in:cartao_credito,pix',
            'valor' => 'required|numeric|min:0',
            'numero_cartao' => 'required_if:metodo,cartao_credito|nullable|digits:16',
            'nome_titular' => 'required_if:metodo,cartao_credito|nullable|string|max:100',
            'validade' => 'required_if:metodo,cartao_credito|nullable|date_format:m/y',
            'cvv' => 'required_if:metodo,cartao_credito|nullable|digits:3',
        ]);

        $status = rand(1, 100) <= 80 ? 'aprovado' : 'recusado';

        $pagamento = Pagamento::create([
            'pedido_id' => $pedido->id,
            'status' => $status,
            'metodo' => $request->metodo,
            'valor' => $request->valor,
            'numero_cartao' => $request->numero_cartao,
            'nome_titular' => $request->nome_titular,
            'validade' => $request->validade,
            'cvv' => $request->cvv,
        ]);

        $pedido->update(['status' => $status == 'aprovado' ? 'Confirmado' : 'Cancelado']);

        $informacao_Status = $status == 'aprovado' ? 'Pagamento aprovado!' : 'Pagamento recusado!';

        return response()->json(['message' => 'Pagamento realizado!', 'status' => $informacao_Status]);
    }

    public function pagarPix(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        $totalPedido = $pedido->itens()->sum(DB::raw('preco * quantidade'));

        $status = rand(1, 100) <= 80 ? 'aprovado' : 'recusado';

        $pagamento = Pagamento::create([
            'pedido_id' => $pedido->id,
            'status' => 'aprovado',
            'metodo' => 'pix',
            'valor' => $totalPedido,
        ]);

        $pedido->update(['status' => $status == 'aprovado' ? 'Confirmado' : 'Cancelado']);

        $informacao_Status = $status == 'aprovado' ? 'Pagamento aprovado!' : 'Pagamento recusado!';

        return response()->json(['message' => 'Pagamento realizado!', 'status' => $informacao_Status]);
    }
}
