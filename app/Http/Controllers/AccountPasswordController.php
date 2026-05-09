<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran dédié « Mon compte → Changer le mot de passe » (OWASP : flux séparé).
 */
class AccountPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.password', [
            'user' => $request->user(),
        ]);
    }
}
