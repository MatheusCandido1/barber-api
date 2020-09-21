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
}
