<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
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

    public function setAppointment($id, Request $request) {

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        $barberService = BarberService::select()
            ->where('id', $service)
            ->where('barber_id', $id)
        ->first();

        if($barberService) {
            $appointmentDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';
            if(strtotime($appointmentDate)  > 0 ) {
                $appointments = UserAppointment::select()
                    ->where('barber_id', $id)
                    ->where('appointment', $appointmentDate)
                ->count();

                if($appointments === 0) {
                    $weekday = date('w', strtotime($appointmentDate));
                    $availability = BarberAvailability::select()
                        ->where('barber_id', $id)
                        ->where('week_day', $weekday)
                    ->first();
                    if($availability) {
                        $hours = explode(',', $availability['hours_available']);
                        if(in_array($hour.':00', $hours)) {
                            $newAppointment = new UserAppointment();
                            $newAppointment->user_id = $this->currentUser->id;
                            $newAppointment->barber_id = $id;
                            $newAppointment->service_id = $service;
                            $newAppointment->appointment = $appointmentDate;
                            $newAppointment->save();
                            
                            return response()->json([
                                'success_message' => 'Horário agendado!'
                            ], 201);
                        } else {
                            return response()->json([
                                'error_message' => 'Erro'
                            ], 400);
                        }
                    } else {
                        return response()->json([
                            'error_message' => 'Erro'
                        ], 400);  
                    }
                } else {
                    return response()->json([
                        'error_message' => 'Erro'
                    ], 400);   
                }
            } else {
                return response()->json([
                    'error_message' => 'Erro'
                ], 400); 
            }
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }

    public function getBarber($id) {

        $barber = Barber::find($id);

        if($barber) {
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $barber['favorite'] = false;
            $barber['images'] = [];
            $barber['services'] = [];
            $barber['testimonials'] = [];
            $barber['availability'] = [];

            // Handling Favorites 

            $favorite = UserFavorite::where('user_id', $this->currentUser->id)
            ->where('barber_id', $barber->id)
            ->count();

            if($favorite > 0) {
                $barber['favorite'] = true; 
            }
            // Handling Images
            $barber['images'] = BarberImage::select(['url'])
            ->where('barber_id', $barber->id)
            ->get();
            foreach($barber['images'] as $key => $value) {
                $barber['images'][$key]['url'] = url('media/uploads/'.$barber['images'][$key]['url']);
            }

            // Handling Services
            $barber['services'] = BarberService::select(['id','name','price'])
            ->where('barber_id', $barber->id)
            ->get();

            // Handling Testimonials
            $barber['testimonials'] = BarberTestimonial::select(['customer_name', 'rate','testimonial'])
            ->where('barber_id', $barber->id)
            ->get();

            // Handling Availability
            $availability = [];
            $available = BarberAvailability::where('barber_id', $barber->id)->get();
            $availableWeekDay = [];
                foreach($available as $item) {
                    $availableWeekDay[$item['week_day']] = explode(',', $item['hours_available']);
                }
            
            $appointments = [];
            $query = UserAppointment::where('barber_id', $barber->id)
            ->whereBetween('appointment', [
                date('Y-m-d').' 00:00:00',
                date('Y-m-d', strtotime('+20 days')).'23:59:59'
            ])
            ->get();

            foreach($query as $item) {
                $appointments[] = $item['appointment'];
            }

            for($i = 0; $i < 20; $i++) {
                $timeItem = strtotime('+'.$i.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availableWeekDay))) {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availableWeekDay[$weekday] as $hourItem) {
                        $dayFormatted = $dayItem.' '.$hourItem.':00';
                        if(!in_array($dayFormatted, $appointments)) {
                            $hours[] = $hourItem;
                        }
                    }

                    if(count($hours) > 0) {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' =>$hours
                        ];
                    }
                }
            }

            $barber['availability'] = $availability;

            return response()->json([
                'data' => $barber,
            ], 200);
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }

    public function searchBarbers(Request $request) {
        $value = $request->input('value');

        if($value) {
            $barbers = Barber::select()
                ->where('name', 'LIKE', '%'.$value.'%')
                ->get();

            
            foreach($barbers as $key => $value) {
                $barbers[$key]['avatar'] = url('media/avatars/'.$barbers[$key]['avatar']);
            }

            return response()->json([
                'data' => $barbers,
            ], 200);    
        } else {
            return response()->json([
                'error_message' => 'Erro'
            ], 400);
        }
    }
}
