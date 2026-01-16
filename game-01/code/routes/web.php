<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CreditReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/export', [CreditReportController::class, 'export']);
