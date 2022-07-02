<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/{path}', 'welcome')->where('path', '([A-z\d\-\/_.]+)?');

Route::get('/', function () {
    return view('welcome');
});



Route::get('/run-artisan', function(){
	Artisan::call('storage:link');
	//Artisan::call('make:mail SendEmail');	
    //Artisan::call('route:cache');
    //Artisan::call('config:clear');
	//Artisan::call('view:clear');
	//Artisan::call('migrate');
    //Artisan::call('make:middleware ForceSSL');
	return "Done";
});


