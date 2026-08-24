<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\B2BApplication;
use App\Support\B2BApplicationAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BusinessAccountLandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $application = null;
        $isB2B = false;

        if ($request->user()) {
            $application = B2BApplication::query()->where('user_id', $request->user()->getKey())->first();
            $isB2B = B2BApplicationAccess::isB2B($request->user());
        }

        return view('business-account.index', compact('application', 'isB2B'));
    }
}
