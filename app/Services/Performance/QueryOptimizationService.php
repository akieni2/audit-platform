<?php

namespace App\Services\Performance;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueryOptimizationService
{
    /**
     * @param  list<string>  $relations
     */
    public function eagerLoad(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * @return list<array{sql: string, time: float}>
     */
    public function slowQueries(float $thresholdMs = 100.0): array
    {
        if (! app()->environment('local', 'testing')) {
            return [];
        }

        return collect(DB::getQueryLog())
            ->filter(fn (array $q) => ($q['time'] ?? 0) >= $thresholdMs)
            ->map(fn (array $q) => ['sql' => $q['query'], 'time' => $q['time']])
            ->values()
            ->all();
    }
}
