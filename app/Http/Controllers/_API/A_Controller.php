<?php

namespace App\Http\Controllers\_API;


use App\Http\Controllers\Controller;
use App\Models\Member;

class A_Controller extends Controller
{

    public function index()
    {
        //
        $members = Member::all();
        return 'FROM A ';
    }
}
