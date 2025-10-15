<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\PagamentoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('login', [AuthController::class, 'login'])->name('login');

// Rotas de produtos
Route::get('produtos', [ProdutoController::class, 'index']);
Route::post('produtos', [ProdutoController::class, 'store']);
Route::get('produtos/{id}', [ProdutoController::class, 'show']);
Route::put('produtos/{id}', [ProdutoController::class, 'update']);
Route::delete('produtos/{id}', [ProdutoController::class, 'destroy']);

Route::get('pedidos', [PedidoController::class, 'index']);
Route::post('pedidos', [PedidoController::class, 'store']);
Route::get('pedidos/{id}', [PedidoController::class, 'show']);
Route::put('pedidos/{id}', [PedidoController::class, 'update']);
Route::delete('pedidos/{id}', [PedidoController::class, 'destroy']);

Route::get('estoques', [EstoqueController::class, 'index']);
Route::get('estoques/{id}', [EstoqueController::class, 'show']);
Route::put('estoques/{id}', [EstoqueController::class, 'update']);
Route::delete('estoques/{id}', [EstoqueController::class, 'destroy']);

Route::post('pedidos/{id}/pagamento', [PagamentoController::class, 'Pagar']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('user', [AuthController::class, 'user'])->name('user');


    // Route::apiResource('produtos', App\Http\Controllers\ProdutoController::class);
});
