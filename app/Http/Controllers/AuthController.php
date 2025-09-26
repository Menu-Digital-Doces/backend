<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validate = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'nome' => $validate['nome'],
            'email' => $validate['email'],
            'password' => bcrypt($validate['password']),
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request) {
        $credencial = $request->only('email', 'password');

        if (!$token = Auth::attempt($credencial)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return response()->json(['token' => $token, 'user' => Auth::user()]);
    }

    public function logout(Request $request) {
        Auth::logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function user() {
        return response()->json(Auth::user());
    }
}