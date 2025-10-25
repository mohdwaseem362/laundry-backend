<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'You reached a protected endpoint',
            'user' => auth()->user()->only(['id','name','email']),
        ]);
    }
}
