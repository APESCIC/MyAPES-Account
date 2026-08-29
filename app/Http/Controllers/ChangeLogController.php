<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ChangeLogPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ChangeLogController extends Controller
{
    public function __invoke(Request $request, ChangeLogPresenter $changeLog): View
    {
        $user = $request->user();

        return view('change-log.index', $changeLog->viewData(
            $user instanceof User ? $user : null,
        ));
    }
}
