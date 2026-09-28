<?php

use Illuminate\Support\Facades\Route;
use Plugins\Recruitment\Http\Controllers\PublicRecruitmentApplicationController;

/*
| Public Recruitment apply / my applications (#290).
| Live paths /recruitment/applications*, /recruitment/{role}/apply
| Mounted from routes/web.php inside auth middleware + plugin.enabled:apes-cic,recruitment.
*/

Route::get('/recruitment/applications', [PublicRecruitmentApplicationController::class, 'index'])
    ->name('recruitment.applications.index');
Route::get('/recruitment/applications/{recruitmentApplication}', [PublicRecruitmentApplicationController::class, 'show'])
    ->name('recruitment.applications.show');
Route::post('/recruitment/applications/{recruitmentApplication}/withdraw', [PublicRecruitmentApplicationController::class, 'withdraw'])
    ->name('recruitment.applications.withdraw');
Route::post('/recruitment/{recruitmentRole}/apply', [PublicRecruitmentApplicationController::class, 'store'])
    ->whereNumber('recruitmentRole')
    ->name('recruitment.apply');
