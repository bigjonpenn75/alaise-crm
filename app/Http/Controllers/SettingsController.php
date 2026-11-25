<?php

namespace App\Http\Controllers;

use App\Models\Business;

class SettingsController extends Controller
{
    public function business(Business $business)
    {
        return view('settings.business', ['business' => $business]);
    }

    public function system()
    {
        return view('settings.system', ['businesses' => Business::orderBy('id')->get()]);
    }
}
