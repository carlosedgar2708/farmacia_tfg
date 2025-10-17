<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function welcome()
    {
        return redirect()->intended(route('inicio'));
    }
}
