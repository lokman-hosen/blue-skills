<?php

use App\Http\Controllers\Worker\Auth\PhoneSignInController;
use Illuminate\Support\Facades\Route;

// Worker sign-in / sign-up by phone (Module 1).
Route::middleware('guest')->prefix('join')->name('worker.')->controller(PhoneSignInController::class)->group(function () {
    Route::get('/', 'language')->name('join');
    Route::get('phone', 'phone')->name('phone');
    Route::post('phone', 'sendCode')->middleware('throttle:10,1')->name('phone.store');
    Route::get('verify', 'verify')->name('verify');
    Route::post('verify', 'confirmCode')->middleware('throttle:10,1')->name('verify.store');
    Route::post('resend', 'resend')->middleware('throttle:5,1')->name('resend');
    Route::get('details', 'details')->name('details');
    Route::post('details', 'register')->middleware('throttle:10,1')->name('details.store');
});
