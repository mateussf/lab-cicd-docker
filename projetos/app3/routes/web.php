<?php

use App\Http\Controllers\Diagnostico\DiagnosticoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/diagnostico', [DiagnosticoController::class, 'exibir']);
