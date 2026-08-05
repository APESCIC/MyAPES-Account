<?php

use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\StaffAdminController;
use App\Http\Controllers\ApesCic\TicketController;
use App\Http\Controllers\Auth\OidcAuthController;
use App\Http\Controllers\Auth\PublicAuthController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PetCare\ConsultationController;
use App\Http\Controllers\PetCare\PetProfileController as PetCarePetProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shelter\CaseController;
use App\Http\Controllers\Shelter\PetProfileController as ShelterPetProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.landing')->name('home');
Route::get('/change-log', ChangeLogController::class)->name('change-log.index');

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

Route::middleware([
    'auth',
    'authorization.context',
    'directory.current',
])->group(function (): void {
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

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin.denial-audit')
        ->group(function (): void {
            Route::get('/', StaffAdminController::class)
                ->middleware('can:admin.access')
                ->name('index');

            Route::get('/users', [AdminUserController::class, 'index'])
                ->middleware('can:admin.users.view')
                ->name('users.index');
            Route::get('/users/{user}', [AdminUserController::class, 'show'])
                ->middleware('can:admin.users.view')
                ->name('users.show');
            Route::put('/users/{user}/roles', [AdminUserController::class, 'updateRoles'])
                ->middleware('can:admin.users.manage')
                ->name('users.roles.update');
            Route::post('/users/{user}/suspension', [AdminUserController::class, 'suspend'])
                ->middleware('can:admin.users.manage')
                ->name('users.suspension.store');
            Route::delete('/users/{user}/suspension', [AdminUserController::class, 'reactivate'])
                ->middleware('can:admin.users.manage')
                ->name('users.suspension.destroy');

            Route::get('/groups', [AdminGroupController::class, 'index'])
                ->middleware('can:admin.groups.view')
                ->name('groups.index');
            Route::post('/groups/sync', [AdminGroupController::class, 'sync'])
                ->middleware('can:admin.group-mappings.manage')
                ->name('groups.sync');
            Route::post('/groups/{directoryGroup}/mappings', [AdminGroupController::class, 'storeMapping'])
                ->middleware('can:admin.group-mappings.manage')
                ->name('groups.mappings.store');
            Route::delete('/groups/mappings/{mapping}', [AdminGroupController::class, 'destroyMapping'])
                ->middleware('can:admin.group-mappings.manage')
                ->name('groups.mappings.destroy');

            Route::get('/roles', [AdminRoleController::class, 'index'])
                ->middleware('can:admin.roles.view')
                ->name('roles.index');
            Route::post('/roles', [AdminRoleController::class, 'store'])
                ->middleware('can:admin.roles.manage')
                ->name('roles.store');
            Route::get('/roles/{role}', [AdminRoleController::class, 'show'])
                ->middleware('can:admin.roles.view')
                ->name('roles.show');
            Route::put('/roles/{role}', [AdminRoleController::class, 'update'])
                ->middleware('can:admin.roles.manage')
                ->name('roles.update');
            Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])
                ->middleware('can:admin.roles.manage')
                ->name('roles.destroy');

            Route::get('/permissions', [AdminPermissionController::class, 'index'])
                ->middleware('can:admin.permissions.view')
                ->name('permissions.index');
        });
});
