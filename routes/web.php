<?php

use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminMaintenanceController;
use App\Http\Controllers\Admin\AdminModuleController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\StaffAdminController;
use App\Http\Controllers\Admin\SuperAdminDashboardController;
use App\Http\Controllers\ApesCic\CaseController as ApesCicCaseController;
use App\Http\Controllers\ApesCic\CaseUpdateController as ApesCicCaseUpdateController;
use App\Http\Controllers\ApesCic\TicketController;
use App\Http\Controllers\Auth\OidcAuthController;
use App\Http\Controllers\Auth\PublicAuthController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PetCare\ConsultationController;
use App\Http\Controllers\PetCare\PetProfileController as PetCarePetProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shelter\CaseController;
use App\Http\Controllers\Shelter\PetProfileController as ShelterPetProfileController;
use App\Http\Controllers\SubCoreController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.landing')->name('home');
Route::get('/change-log', ChangeLogController::class)->name('change-log.index');
Route::get('/storage/pet-profiles/{path?}', static fn () => abort(404))
    ->where('path', '.*');

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
    Route::prefix('admin/maintenance')
        ->name('admin.maintenance.')
        ->middleware([
            'maintenance.recovery',
            'admin.denial-audit',
            'can:admin.maintenance.manage',
        ])
        ->group(function (): void {
            Route::get('/', [AdminMaintenanceController::class, 'index'])
                ->name('index');
            Route::post('/activate', [AdminMaintenanceController::class, 'activate'])
                ->name('activate');
            Route::post('/deactivate', [AdminMaintenanceController::class, 'deactivate'])
                ->name('deactivate');
        });

    Route::get('/email/verify', function (Request $request) {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('onboarding.edit')
            : view('auth.verify-email');
    })->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('onboarding.edit');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/onboarding', [OnboardingController::class, 'edit'])->name('onboarding.edit');
    Route::put('/onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');
});

Route::middleware([
    'auth',
    'authorization.context',
    'directory.current',
    'account.ready',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/staff-photo', [ProfileController::class, 'staffPhoto'])->name('profile.staff-photo');

    Route::prefix('apes-cic')->name('apes-cic.')->group(function (): void {
        Route::get('/', [SubCoreController::class, 'show'])
            ->defaults('subCoreKey', 'apes-cic')
            ->middleware('service.selected:apes-cic')
            ->name('index');
        Route::middleware(['module.available:apes-cic,tickets', 'service.selected:apes-cic'])
            ->group(function (): void {
                Route::get('tickets', [TicketController::class, 'index'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.index');
                Route::post('tickets', [TicketController::class, 'store'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.store');
                Route::get('tickets/{ticket}', [TicketController::class, 'show'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.show');
                Route::match(['put', 'patch'], 'tickets/{ticket}', [TicketController::class, 'update'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.update');
                Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.destroy');
            });
        Route::middleware(['module.available:apes-cic,cases', 'service.selected:apes-cic'])
            ->group(function (): void {
                Route::get('cases', [ApesCicCaseController::class, 'index'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.index');
                Route::post('cases', [ApesCicCaseController::class, 'store'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.store');
                Route::get('cases/{case}', [ApesCicCaseController::class, 'show'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.show');
                Route::match(['put', 'patch'], 'cases/{case}', [ApesCicCaseController::class, 'update'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.update');
                Route::delete('cases/{case}', [ApesCicCaseController::class, 'destroy'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.destroy');
                Route::post('cases/{case}/updates', [ApesCicCaseUpdateController::class, 'store'])
                    ->defaults('subCoreKey', 'apes-cic')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.updates.store');
            });
    });

    Route::prefix('shelter')->name('shelter.')->group(function (): void {
        Route::get('/', [SubCoreController::class, 'show'])
            ->defaults('subCoreKey', 'shelter-rescue')
            ->middleware('service.selected:shelter-rescue')
            ->name('index');
        Route::middleware(['module.available:shelter-rescue,tickets', 'service.selected:shelter-rescue'])
            ->group(function (): void {
                Route::get('tickets', [TicketController::class, 'index'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.index');
                Route::post('tickets', [TicketController::class, 'store'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.store');
                Route::get('tickets/{ticket}', [TicketController::class, 'show'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.show');
                Route::match(['put', 'patch'], 'tickets/{ticket}', [TicketController::class, 'update'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.update');
            });
        Route::middleware(['module.available:shelter-rescue,pet-profiles', 'service.selected:shelter-rescue'])
            ->group(function (): void {
                Route::get('pets/{pet}/photo', [ShelterPetProfileController::class, 'photo'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'pet-profiles')
                    ->name('pets.photo');
                Route::resource('pets', ShelterPetProfileController::class)
                    ->only(['index', 'store', 'show', 'update'])
                    ->parameters(['pets' => 'pet']);
            });
        Route::middleware(['module.available:shelter-rescue,cases', 'service.selected:shelter-rescue'])
            ->group(function (): void {
                Route::resource('cases', CaseController::class)
                    ->only(['index', 'store', 'show', 'update'])
                    ->parameters(['cases' => 'case']);
                Route::post('cases/{case}/updates', [ApesCicCaseUpdateController::class, 'store'])
                    ->defaults('subCoreKey', 'shelter-rescue')
                    ->defaults('moduleKey', 'cases')
                    ->name('cases.updates.store');
            });
    });

    Route::prefix('petcare')->name('petcare.')->group(function (): void {
        Route::get('/', [SubCoreController::class, 'show'])
            ->defaults('subCoreKey', 'pet-care-clinic')
            ->middleware('service.selected:pet-care-clinic')
            ->name('index');
        Route::middleware(['module.available:pet-care-clinic,tickets', 'service.selected:pet-care-clinic'])
            ->group(function (): void {
                Route::get('tickets', [TicketController::class, 'index'])
                    ->defaults('subCoreKey', 'pet-care-clinic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.index');
                Route::post('tickets', [TicketController::class, 'store'])
                    ->defaults('subCoreKey', 'pet-care-clinic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.store');
                Route::get('tickets/{ticket}', [TicketController::class, 'show'])
                    ->defaults('subCoreKey', 'pet-care-clinic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.show');
                Route::match(['put', 'patch'], 'tickets/{ticket}', [TicketController::class, 'update'])
                    ->defaults('subCoreKey', 'pet-care-clinic')
                    ->defaults('moduleKey', 'tickets')
                    ->name('tickets.update');
            });
        Route::middleware(['module.available:pet-care-clinic,pet-profiles', 'service.selected:pet-care-clinic'])
            ->group(function (): void {
                Route::get('pets/{pet}/photo', [PetCarePetProfileController::class, 'photo'])
                    ->defaults('subCoreKey', 'pet-care-clinic')
                    ->defaults('moduleKey', 'pet-profiles')
                    ->name('pets.photo');
                Route::resource('pets', PetCarePetProfileController::class)
                    ->only(['index', 'store', 'show', 'update'])
                    ->parameters(['pets' => 'pet']);
            });
        Route::middleware(['module.available:pet-care-clinic,consultations', 'service.selected:pet-care-clinic'])
            ->group(function (): void {
                Route::resource('consultations', ConsultationController::class)
                    ->only(['index', 'store', 'show', 'update'])
                    ->parameters(['consultations' => 'consultation']);
            });
    });

    Route::prefix('superadmin')
        ->name('superadmin.')
        ->middleware('admin.denial-audit')
        ->group(function (): void {
            Route::get('/', SuperAdminDashboardController::class)
                ->middleware('can:superadmin.access')
                ->name('index');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin.denial-audit')
        ->group(function (): void {
            Route::get('/', StaffAdminController::class)
                ->middleware('can:admin.analytics.view')
                ->name('index');

            Route::get('/users', [AdminUserController::class, 'index'])
                ->middleware('can:admin.users.view')
                ->name('users.index');
            Route::get('/users/{user}', [AdminUserController::class, 'show'])
                ->middleware('can:admin.users.view')
                ->name('users.show');
            Route::put('/users/{user}/profile', [AdminUserController::class, 'updateProfile'])
                ->middleware('can:admin.users.manage')
                ->name('users.profile.update');
            Route::put('/users/{user}/staff-profile', [AdminUserController::class, 'updateStaffProfile'])
                ->middleware('can:admin.users.manage')
                ->name('users.staff-profile.update');
            Route::get('/users/{user}/staff-photo', [AdminUserController::class, 'staffPhoto'])
                ->middleware('can:admin.users.view')
                ->name('users.staff-photo');
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

            Route::get('/modules', [AdminModuleController::class, 'index'])
                ->middleware('can:admin.modules.view')
                ->name('modules.index');
            Route::post('/modules/{subCoreKey}/{moduleKey}/transition', [AdminModuleController::class, 'transition'])
                ->middleware('can:admin.modules.manage')
                ->name('modules.transition');
        });
});
