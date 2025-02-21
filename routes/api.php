<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardAIController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/ask-openai', [DashboardAIController::class, 'askOpenAI']);

Route::get('/chart-data', function () {
    return response()->json([
        'labels' => ['January', 'February', 'March', 'April'],
        'values' => [10, 20, 15, 25]
    ]);
});

