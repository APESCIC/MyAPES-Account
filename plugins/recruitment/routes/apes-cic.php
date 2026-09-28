<?php

use Illuminate\Support\Facades\Route;
use Plugins\Recruitment\Http\Controllers\RecruitmentApplicationController;
use Plugins\Recruitment\Http\Controllers\RecruitmentRoleController;

/*
| APES CIC Recruitment staff manage (#290).
| Live prefix /apes-cic/recruitment* — names apes-cic.recruitment.*
*/

Route::get('recruitment', [RecruitmentRoleController::class, 'index'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.index');
Route::post('recruitment', [RecruitmentRoleController::class, 'store'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.store');
Route::get('recruitment/applications', [RecruitmentApplicationController::class, 'index'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.applications.index');
Route::get('recruitment/applications/{recruitmentApplication}', [RecruitmentApplicationController::class, 'show'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.applications.show');
Route::match(['put', 'patch'], 'recruitment/applications/{recruitmentApplication}', [RecruitmentApplicationController::class, 'update'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.applications.update');
Route::get('recruitment/{recruitmentRole}', [RecruitmentRoleController::class, 'show'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.show');
Route::match(['put', 'patch'], 'recruitment/{recruitmentRole}', [RecruitmentRoleController::class, 'update'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.update');
Route::post('recruitment/{recruitmentRole}/publish', [RecruitmentRoleController::class, 'publish'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.publish');
Route::post('recruitment/{recruitmentRole}/close', [RecruitmentRoleController::class, 'close'])
    ->defaults('subCoreKey', 'apes-cic')
    ->defaults('moduleKey', 'recruitment')
    ->name('recruitment.close');
