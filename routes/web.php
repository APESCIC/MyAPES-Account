<?php

use App\Http\Controllers\Admin\StaffAdminController;
use App\Http\Controllers\ApesCic\TicketController;
use App\Http\Controllers\Auth\OidcAuthController;
use App\Http\Controllers\Auth\PublicAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PetCare\ConsultationController;
use App\Http\Controllers\PetCare\PetProfileController as PetCarePetProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shelter\CaseController;
use App\Http\Controllers\Shelter\PetProfileController as ShelterPetProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.landing')->name('home');

Route::middleware('guest')->controller(PublicAuthController::class)->group(function (): void {
    Route::get('/login', 'showLogin')->name('public.login');
    Route::post('/login', 'login')->middleware('throttle:public-login')->name('public.login.submit');
    Route::get('/register', 'showRegister')->name('public.register');
    Route::post('/register', 'register')->name('public.register.submit');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/staff/login', function () {
        return view('auth.staff-login');
    })->name('staff.login');
    Route::post('/staff/login', [PublicAuthController::class, 'localStaffLogin'])->name('staff.local-login.submit');
});

Route::post('/qa/switch-role', [PublicAuthController::class, 'qaSwitchRole'])->name('qa.switch-role');

Route::prefix('staff/auth')->controller(OidcAuthController::class)->group(function (): void {
    Route::get('/login', 'login')->name('staff.auth.login');
    Route::get('/callback', 'callback')->name('staff.auth.callback');
    Route::post('/logout', 'logout')->middleware('auth')->name('auth.logout');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('apes-cic')->name('apes-cic.')->group(function (): void {
        Route::resource('tickets', TicketController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->parameters(['tickets' => 'ticket']);
    });

    Route::prefix('shelter')->name('shelter.')->group(function (): void {
        Route::resource('pets', ShelterPetProfileController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['pets' => 'pet']);
        Route::resource('cases', CaseController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['cases' => 'case']);
    });

    Route::prefix('petcare')->name('petcare.')->group(function (): void {
        Route::resource('pets', PetCarePetProfileController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['pets' => 'pet']);
        Route::resource('consultations', ConsultationController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['consultations' => 'consultation']);
    });

    Route::get('/admin', StaffAdminController::class)
        ->middleware('role:admin,superadmin')
        ->name('admin.index');
});
