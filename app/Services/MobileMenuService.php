<?php

namespace App\Services;

use App\Models\Department;
use App\Models\MobileMenuAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MobileMenuService
{
    public function isMobileApplication(Request $request): bool
    {
        return Str::contains(
            (string) $request->userAgent(),
            (string) config('mobile_menu.user_agent_marker', 'DGCPT-Android/')
        );
    }

    /** @return array<string, array{label:string,description:string,routes:array<int,string>}> */
    public function catalog(): array
    {
        return config('mobile_menu.menus', []);
    }

    /** @return list<string> */
    public function allowedKeys(User $user): array
    {
        $catalogKeys = array_keys($this->catalog());

        if ($user->canAccessAdministrationMenu()) {
            return $catalogKeys;
        }

        $individual = $this->activeAssignments(MobileMenuAssignment::SUBJECT_USER, [(int) $user->id]);
        if ($individual->isNotEmpty()) {
            return $individual->pluck('menu_key')->intersect($catalogKeys)->values()->all();
        }

        $keys = collect();
        if ($user->role_id !== null) {
            $keys = $keys->merge(
                $this->activeAssignments(MobileMenuAssignment::SUBJECT_ROLE, [(int) $user->role_id])->pluck('menu_key')
            );
        }

        if ($user->department_id !== null) {
            $keys = $keys->merge(
                $this->activeAssignments(
                    MobileMenuAssignment::SUBJECT_DEPARTMENT,
                    Department::ancestryIds((int) $user->department_id)
                )->pluck('menu_key')
            );
        }

        return $keys->unique()->intersect($catalogKeys)->values()->all();
    }

    public function routeMenuKey(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach ($this->catalog() as $key => $menu) {
            foreach ($menu['routes'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $key;
                }
            }
        }

        return null;
    }

    public function canAccessRoute(User $user, ?string $routeName): bool
    {
        foreach (config('mobile_menu.always_allowed_routes', []) as $pattern) {
            if ($routeName !== null && Str::is($pattern, $routeName)) {
                return true;
            }
        }

        $menuKey = $this->routeMenuKey($routeName);

        return $menuKey === null || in_array($menuKey, $this->allowedKeys($user), true);
    }

    private function activeAssignments(string $subjectType, array $subjectIds): Collection
    {
        if ($subjectIds === []) {
            return collect();
        }

        return MobileMenuAssignment::query()
            ->active()
            ->where('subject_type', $subjectType)
            ->whereIn('subject_id', $subjectIds)
            ->get();
    }
}
