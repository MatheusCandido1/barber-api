<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Barber;
use App\Models\BarberImage;
use App\Models\BarberService;
use App\Models\BarberTestimonial;
use App\Models\BarberAvailability;

class BarberController extends Controller
{
    private $currentUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->currentUser = auth()->user();
    }

    private function geolocation($address) {
        $key = env('MAPS_KEY', null);
        $address = urlencode($address);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getBarbers(Request $request) {
        
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $city = $request->input('city');
        $offset = $request->input('offset');
        if(!$offset) 
            $offset = 0;

        if(!empty($city)) {
            $response = $this->geolocation($city);

            if($response['results']) {
                $latitude = $response['results'][0]['geometry']['location']['lat'];
                $longitude = $response['results'][0]['geometry']['location']['lng'];
            }
        } elseif(!empty($latitude) && !empty($longitude)) {
            $response = $this->geolocation($latitude.','.$longitude);

            if($response['results']) {
                $city = $response['results'][0]['formatted_address'];
            }
        } else {
            $latitude = '-23.5630907';
            $longitude = '-46.6682795';
            $city = 'São Paulo';
        }

        
        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$latitude.'), 2) +
            POW(69.1 * ('.$longitude.' - longitude) * COS(latitude / 57.3) , 2)) as distance'))
            ->havingRaw('distance < ?', [5])
            ->orderBy('distance', 'ASC')
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach($barbers as $key => $value) {
            $barbers[$key]['avatar'] = url('media/avatars/'.$barbers[$key]['avatar']);
        }

        return response()->json([
            'data' => $barbers,
            'location' => 'São Paulo'
        ], 200);
    }
}
