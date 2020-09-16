<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['register']]);
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
            $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);

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
                    'error' => 'Unauthorized'
                ], 401);
            }
        } else {
            return response()->json([
                'error_message' => $validator->errors()
            ], 400);
        }
        
    }
}
