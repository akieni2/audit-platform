<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
            'roleOptions' => UserRoles::all(),
        ]);
    }

    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $newRole = $request->validated('role');

        if ($request->user()->id === $user->id
            && $user->isAdmin()
            && $newRole !== UserRoles::ADMIN) {
            return back()->withErrors([
                'role' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.',
            ]);
        }

        $adminCount = User::query()->where('role', UserRoles::ADMIN)->count();
        if ($user->isAdmin()
            && $newRole !== UserRoles::ADMIN
            && $adminCount <= 1) {
            return back()->withErrors([
                'role' => 'Impossible de retirer le dernier administrateur de la plateforme.',
            ]);
        }

        $user->update(['role' => $newRole]);

        return back()->with('status', 'Rôle mis à jour pour '.$user->email.'.');
    }
}
