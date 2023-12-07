<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\BoardController;

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

Route::middleware('api')->group(function () {
    //space CRUD
    Route::post('/spaces', [SpaceController::class, 'store']);
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{id}', [SpaceController::class, 'show']);
    Route::put('/spaces/{id}', [SpaceController::class, 'update']);
    Route::delete('/spaces/{id}', [SpaceController::class, 'destroy']);

    //space share user CRUD
    Route::get('/spaces/get_share_user/{id}', [SpaceController::class, 'get_share_user']);
    Route::put('/spaces/update_share_user/{id}', [SpaceController::class, 'update_share_user']);
    Route::delete('/spaces/delete_share_user/{id}', [SpaceController::class, 'delete_share_user']);

    //board CRUD
    //Route::resource('boards', BoardController::class);
    Route::post('/boards', [BoardController::class, 'store']);
    Route::get('/boards', [BoardController::class, 'index']);
    Route::get('/boards/{id}', [BoardController::class, 'show']);
    Route::put('/boards/{id}', [BoardController::class, 'update']);
    Route::delete('/boards/{id}', [BoardController::class, 'destroy']);

    Route::get('/boards/get_share_user/{id}', [BoardController::class, 'get_share_user']);
    Route::put('/boards/update_share_user/{id}', [BoardController::class, 'update_share_user']);
    Route::delete('/boards/delete_share_user/{id}', [BoardController::class, 'delete_share_user']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
