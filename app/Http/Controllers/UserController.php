<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private $currentUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->currentUser = auth()->user();
    }

    public function details() {
        $user = $this->currentUser;
        $user['avatar'] = url('media/avatars/'.$user['avatar']);

        return response()->json([
            'data' => $user,
        ], 200);
    }
}
