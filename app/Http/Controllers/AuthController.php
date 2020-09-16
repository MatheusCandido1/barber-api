<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['register','login']]);
    }

    public function register(Request $request) {  

        $validator = Validator::make($request->all() , [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required' 
        ], [
            'required' => 'O campo :attribute é obrigatório'
        ]);

        if($validator->passes()) {
            $user = new User();
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = bcrypt($request->input('password'));

            $user->save();

            $credentials = $request->only('email','password');

            $token = auth()->attempt($credentials);

            $data = auth()->user();
            $data['avatar'] = url('media/avatars/'.$data['avatar']);

            if($token) {
                return response()->json([
                    'data' => $data,
                    'token' => $token
                ], 200);
            } else {
                return response()->json([
                    'error_message' => 'Não autorizado'
                ], 401);
            }
        } else {
            return response()->json([
                'error_message' => $validator->errors()
            ], 400);
        }
        
    }

    public function login(Request $request) {

        $credentials = $request->only('email','password');
        $token = auth()->attempt($credentials);

        if(!$token) {
            return response()->json([
                'error' => 'E-mail e/ou senha incorretos'
            ], 401);
        }

        $data = auth()->user();
        $data['avatar'] = url('media/avatars/'.$data['avatar']);
            return response()->json([
                'success_message' => 'Login realizado',
                'data' => $data,
                'token' => $token
            ], 200);
    }

    public function logout() {
        auth()->logout();
        return response()->json([
            'success_message' => 'Logout realizado'
        ], 200);
    }

    public function refresh() {
        $token = auth()->refresh();
        $data = auth()->user();
        $data['avatar'] = url('media/avatars/'.$data['avatar']);
            return response()->json([
                'data' => $data,
                'token' => $token
            ], 200);
    }
}
