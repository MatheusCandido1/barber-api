<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Barber;
use App\Models\UserFavorite;
use App\Models\UserAppointment;
use App\Models\BarberService;

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

    public function getFavorites() {
        $barbers = [];
        $favorites = UserFavorite::select()
            ->where('user_id', $this->currentUser->id)
        ->get();

        if($favorites) {
            foreach($favorites as $favorite) {
                $barber = Barber::find($favorite['barber_id']);
                $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
                $barbers[] = $barber;
            }
        }
        return response()->json([
            'data' => $barbers,
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
                    'favorite' => false,
                    'success_message' => 'Barbeiro removido dos favoritos',
                ], 201);
            } else {
                $favorite = new UserFavorite();
                $favorite->user_id = $this->currentUser->id;
                $favorite->barber_id = $barber_id;
                $favorite->save();

                return response()->json([
                    'favorite' => true,
                    'success_message' => 'Barbeiro favoritado',
                ], 201);
            }
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }

    public function getAppointments() {
        $response = [];
        $appointments = UserAppointment::select()
            ->where('user_id', $this->currentUser->id)
            ->orderBy('appointment','DESC')
        ->get();

        if($appointments) {
            foreach($appointments as $appointment) {
                $barber = Barber::find($appointment['barber_id']);
                $barber['avatar'] = url('media/avatars/'.$barber['avatar']);

                $service = BarberService::find($appointment['service_id']);

                $response[] = [
                    'id' => $appointment['id'],
                    'appointment' => $appointment['appointment'],
                    'barber' => $barber,
                    'service' => $service
                ];

                return response()->json([
                    'data' => $response,
                    'success_message' => 'Barbeiro favoritado',
                ], 200);
            }
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }
}
