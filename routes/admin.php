<?php

use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffInvitationAcceptanceController;
use Illuminate\Support\Facades\Route;

// Staff management (Module 1D). Staff screens need confirmed 2FA (D-035).
Route::middleware(['auth', 'verified', 'user.type:staff', 'staff.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('invitations', 'invite')->middleware('throttle:30,1')->name('invitations.store');
        Route::delete('invitations/{invitation}', 'revoke')->name('invitations.destroy');
        Route::patch('members/{member}', 'updateRole')->name('members.update');
        Route::delete('members/{member}', 'deactivate')->name('members.destroy');
    });
});

Route::middleware(['guest', 'throttle:20,1'])->controller(StaffInvitationAcceptanceController::class)->group(function () {
    Route::get('staff-invitations/{token}', 'show')->where('token', '[A-Za-z0-9]{48}')->name('staff-invitations.show');
    Route::post('staff-invitations/{token}', 'store')->where('token', '[A-Za-z0-9]{48}')->name('staff-invitations.store');
});
