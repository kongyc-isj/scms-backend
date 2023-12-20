<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\FieldKeyController;
use App\Http\Controllers\LanguageController;

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
    Route::apiResource('spaces', SpaceController::class);

    //customize space share user CRUD
    Route::get('/spaces/get_share_user/{id}', [SpaceController::class, 'get_share_user']);
    Route::put('/spaces/update_share_user/{id}', [SpaceController::class, 'update_share_user']);
    Route::delete('/spaces/delete_share_user/{id}', [SpaceController::class, 'delete_share_user']);

    //board CRUD
    Route::apiResource('boards', BoardController::class);

    //customize board share user CRUD
    Route::get('/boards/get_share_user/{id}', [BoardController::class, 'get_share_user']);
    Route::post('/boards/create_share_user/{id}', [BoardController::class, 'create_share_user']);
    Route::put('/boards/update_share_user/{id}', [BoardController::class, 'update_share_user']);
    Route::delete('/boards/delete_share_user/{id}', [BoardController::class, 'delete_share_user']);
    Route::put('/boards/update_api_key/{id}', [BoardController::class, 'update_api_key']);

    //component CRUD
    Route::apiResource('components', ComponentController::class);

    //field key CRUD
    Route::apiResource('field_key', FieldKeyController::class);
    
    //language R
    Route::get('/languages', [LanguageController::class, 'index']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
