<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1'], function () {
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('/details', 'AuthController@details');
        Route::get('/logout', 'AuthController@logout');
    });
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
//Branch
Route::get('/branch', 'BranchController@index');
Route::get('/branches', 'BranchController@all'); //all
Route::get('/branch/{id}', 'BranchController@show');
Route::post('/branch', 'BranchController@store');
Route::put('/branch/{id}', 'BranchController@update');
Route::delete('/branch/{id}', 'BranchController@destroy');

//Studio
Route::get('/studio', 'StudioController@index');
Route::get('/studios', 'StudioController@all'); //all
Route::get('/studio/{id}', 'StudioController@show');
Route::post('/studio', 'StudioController@store');
Route::put('/studio/{id}', 'StudioController@update');
Route::delete('/studio/{id}', 'StudioController@destroy');

//Movies
Route::get('/movie', 'MovieController@index');
Route::get('/movies', 'MovieController@all'); //all
Route::get('/movie/{id}', 'MovieController@show');
Route::post('/movie', 'MovieController@store');
Route::put('/movie/{id}', 'MovieController@update');
Route::delete('/movie/{id}', 'MovieController@destroy');

//schedule
Route::get('/schedule', 'ScheduleController@index');
Route::get('/user/schedule', 'ScheduleController@user');
Route::post('/schedule/filter', 'ScheduleController@filter');
Route::get('/schedule/{id}', 'ScheduleController@show');
Route::post('/schedule', 'ScheduleController@store');
Route::put('/schedule/{id}', 'ScheduleController@update');
Route::delete('/schedule/{id}', 'ScheduleController@destroy');

Route::post('/dummy', 'DummyController@store');
Route::get('/dummy/{id}', 'DummyController@show');
Route::get('/dummy/', 'DummyController@index');
