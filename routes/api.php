<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::name('rnc.')->prefix('rnc')->controller(\App\Http\Controllers\RNCController::class)->group( function () {
    Route::get('/{rnc}', 'index')->name('index');
});
