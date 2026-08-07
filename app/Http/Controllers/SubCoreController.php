<?php

namespace App\Http\Controllers;

use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubCoreController extends Controller
{
    public function show(
        Request $request,
        ModuleRegistry $registry,
        ModuleNavigationProvider $navigation,
        string $subCoreKey,
    ): View {
        return view('sub-cores.show', [
            'subCore' => $registry->subCore($subCoreKey),
            'modules' => $navigation->forSubCore(
                $request->user(),
                $subCoreKey,
            ),
        ]);
    }
}
