<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Barber;
use App\Models\UserFavorite;

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

    public function setFavorites(Request $request) {
        $barber_id = $request->input('barber');

        $barber = Barber::find($barber_id);

        if($barber) {
            $favorite = UserFavorite::select()
                ->where('user_id', $this->currentUser->id)
                ->where('barber_id', $barber_id)
            ->first();


            if($favorite) {
                $favorite->delete();
                return response()->json([
                    'success_message' => 'Barbeiro removido dos favoritos',
                ], 201);
            } else {
                $favorite = new UserFavorite();
                $favorite->user_id = $this->currentUser->id;
                $favorite->barber_id = $barber_id;
                $favorite->save();

                return response()->json([
                    'success_message' => 'Barbeiro favoritado',
                ], 201);
            }
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }
}
