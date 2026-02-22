<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dl/{token}', [\App\Http\Controllers\DownloadController::class, 'proxy'])->name('download.proxy');
