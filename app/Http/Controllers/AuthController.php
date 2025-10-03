<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);



        $user = User::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'password' => bcrypt($validate['password']),
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $credencial = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credencial)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => Auth::guard('api')->user()
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function user()
    {
        // dd('texto comum');
        // exit();

        return response()->json(Auth::guard('api')->user());
    }
}
