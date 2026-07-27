<?php

namespace App\Http\Controllers;

use App\Support\ReleaseHistoryRepository;
use Illuminate\Contracts\View\View;

class ChangeLogController extends Controller
{
    public function __invoke(ReleaseHistoryRepository $releases): View
    {
        return view('change-log.index', [
            'currentRelease' => $releases->current(),
            'releases' => $releases->all(),
        ]);
    }
}
