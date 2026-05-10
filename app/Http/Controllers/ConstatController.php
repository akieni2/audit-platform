<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\View\View;

class ConstatController extends Controller
{
    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('constats.index', compact('mission'));
    }
}
