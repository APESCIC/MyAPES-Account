<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Wave 0 stub: empty index so hub #list / #create routes resolve.
 * Full CRUD lands in Wave 1 (#213 / #220).
 */
class RecruitmentRoleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canCreate = $user !== null && $user->can('apes-cic.recruitment.create');

        return view('apes-cic.recruitment.index', [
            'canCreate' => $canCreate,
        ]);
    }
}
