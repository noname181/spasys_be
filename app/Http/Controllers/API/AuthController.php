<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Member;


class AuthController extends Controller
{

     public function index()
    {
        //
        $members = Member::all();
        return $members;
    }

}