<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/export', [ExportController::class, 'export'])->name('export');
Route::get('/advanced-export', [ExportController::class, 'advancedExport'])->name('advanced-export');

// CSRF Token route for AJAX requests
Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

