<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        $term = trim((string) $request->query('q', ''));

        $missions = collect();
        if (strlen($term) >= 2) {
            $missions = Mission::query()
                ->visibleToUser($user)
                ->where(function ($q) use ($term) {
                    $like = '%'.$term.'%';
                    $q->where('organisation', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->with(['department:id,code,name'])
                ->orderByDesc('updated_at')
                ->limit(40)
                ->get();
        }

        return view('search.results', [
            'missions' => $missions,
            'term' => $term,
        ]);
    }
}
