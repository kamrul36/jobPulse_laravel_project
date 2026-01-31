<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return ResponseHelper::respond('v1', 'Welcome to the Job Portal API', 'GET',200);

    }
}
