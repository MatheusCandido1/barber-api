<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

use App\Models\Barber;
use App\Models\UserFavorite;
use App\Models\UserAppointment;
use App\Models\BarberService;
use App\Models\User;

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
                    'success_message' => '',
                ], 200);
            }
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }

    public function update(Request $request) {
        $rules = [
          'name' => 'min:2',
          'email' => 'email|unique:users',
          'password' => 'same:password_confirmation',
          'password_confirmation' => 'same:password_confirmation'
        ];

        $validator = Validator::make($request->all(), $rules);
        
        if($validator->fails()) {
            return response()->json([
                'error_message' => $validator->messages()
            ], 400);
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');

        $user = User::find($this->currentUser->id);

        if($name) {
            $user->name = $name;
        }

        if ($email) {
            $user->email = $email;
        }

        if ($password) {
            $user->password = bcrypt($request->input('password'));
        }

        $user->save();

        return response()->json([
            'success_message' => 'Perfil atualizado',
        ], 200);
    }

    public function setAvatar(Request $request) {
        $rules = [
            'avatar' => 'required|image|mimes:png,jpg,jpeg'
        ];
        
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return response()->json([
                'error_message' => $validator->messages()
            ], 400);
        }

        $avatar = $request->file('avatar');

        $folder = public_path('/media/avatars');
        $filename = md5(time().rand(0,9999)).'.jpg';

        $image = Image::make($avatar->getRealPath());
        $image->fit(300,300)->save($folder.'/'.$filename);

        $user = User::find($this->currentUser->id);
        $user->avatar = $filename;
        $user->save();

        return response()->json([
            'success_message' => 'Avatar atualizado',
        ], 200);
    }
}
