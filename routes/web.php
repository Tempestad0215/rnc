<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', fn() => response()->noContent())->name('up');


Route::get('/', function () {
    return redirect()->route('up');
});
