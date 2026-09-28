<?php

use Illuminate\Support\Facades\Route;
use Plugins\Cases\Http\Controllers\CaseController;
use Plugins\Cases\Http\Controllers\CaseUpdateController;

/*
| Shelter Cases staff routes (#289).
| Live prefix /shelter/cases — names shelter.cases.*
*/

Route::get('cases', [CaseController::class, 'index'])
    ->defaults('subCoreKey', 'shelter-rescue')
    ->defaults('moduleKey', 'cases')
    ->name('cases.index');
Route::post('cases', [CaseController::class, 'store'])
    ->defaults('subCoreKey', 'shelter-rescue')
    ->defaults('moduleKey', 'cases')
    ->name('cases.store');
Route::get('cases/{case}', [CaseController::class, 'show'])
    ->defaults('subCoreKey', 'shelter-rescue')
    ->defaults('moduleKey', 'cases')
    ->name('cases.show');
Route::match(['put', 'patch'], 'cases/{case}', [CaseController::class, 'update'])
    ->defaults('subCoreKey', 'shelter-rescue')
    ->defaults('moduleKey', 'cases')
    ->name('cases.update');
Route::post('cases/{case}/updates', [CaseUpdateController::class, 'store'])
    ->defaults('subCoreKey', 'shelter-rescue')
    ->defaults('moduleKey', 'cases')
    ->name('cases.updates.store');
