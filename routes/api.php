<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\TicketController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//passenger
Route::group(['prefix' => 'passenger'], function () {
    Route::post('/add', [PassengerController::class, 'addPassenger']);
    Route::get('/view', [PassengerController::class, 'viewPassenger']);
    Route::post('/update', [PassengerController::class, 'updatePassenger']);
    Route::post('/delete', [PassengerController::class, 'deletePassenger']);
});

//view all passenger
Route::group(['prefix' => 'passengers'], function () {
    Route::get('/all', [PassengerController::class, 'viewAllPassengers']);
    Route::get('/notAllocated', [PassengerController::class, 'viewNotAllocatedpassengers']);
});

//ticket
Route::group(['prefix' => 'ticket'], function () {
    Route::post('/add', [TicketController::class, 'addTicket']);
    Route::get('/view', [TicketController::class, 'viewTicket']);
    Route::post('/update', [TicketController::class, 'updateTicket']);
    Route::delete('/delete', [TicketController::class, 'deleteTicket']);
});

//view all tickets
Route::group(['prefix' => 'tickets'], function () {
    Route::get('/all', [TicketController::class, 'viewAllTickets']);
});

//ticket allocation to passenger
Route::group(['prefix' => 'ticket'], function () {
    Route::post('/allocate', [TicketController::class, 'ticketAllocationToPassenger']);
});
