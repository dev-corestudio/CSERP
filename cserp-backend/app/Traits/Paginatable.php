<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait Paginatable
 *
 * Dodaje spójną obsługę paginacji server-side do kontrolerów.
 * Frontend wysyła: page, per_page, sort_by, sort_dir, search
 * Backend zwraca standardową paginowaną odpowiedź Laravela.
 */
trait Paginatable
{
    /**
     * Zastosuj sortowanie z walidacją dozwolonych kolumn.
     */
    protected function applySorting(Builder $query, Request $request, array $allowedSortColumns, string $defaultSort = 'created_at', string $defaultDir = 'desc'): Builder
    {
        $sortBy = $request->get('sort_by', $defaultSort);
        $sortDir = $request->get('sort_dir', $defaultDir);

        // Whitelist kolumn - zabezpieczenie przed SQL injection
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = $defaultSort;
        }

        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? $sortDir : $defaultDir;

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * Zwróć paginowaną odpowiedź.
     *
     * per_page: 1-100 (domyślnie 15)
     */
    protected function paginateQuery(Builder $query, Request $request, int $defaultPerPage = 15)
    {
        $perPage = min(max((int) $request->get('per_page', $defaultPerPage), 1), 100);

        return $query->paginate($perPage)->withQueryString();
    }
}
