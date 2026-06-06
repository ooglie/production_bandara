<?php

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, HomePageService $homePageService)
    {
        return view('home', $homePageService->build());
    }
}
