<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CfdtAccessController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isInstitutionalSuperAdmin(), 403);

        $users = User::query()
            ->with('department')
            ->where('active', true)
            ->orderBy('name')
            ->orderBy('prenom')
            ->paginate(30);

        return view('cfdt.access', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->isInstitutionalSuperAdmin(), 403);

        $data = $request->validate([
            'cfdt_role' => ['nullable', Rule::in(['learner', 'trainer', 'validator', 'administrator'])],
        ]);

        $user->update(['cfdt_role' => $data['cfdt_role'] ?? null]);

        return back()->with('status', 'Habilitation CFDT mise à jour.');
    }
}
