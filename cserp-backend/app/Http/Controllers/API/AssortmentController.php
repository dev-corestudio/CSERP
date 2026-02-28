<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Assortment;
use App\Enums\AssortmentType;
use App\Enums\AssortmentUnit;
use App\Enums\AssortmentHistoryAction;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssortmentController extends Controller
{
    use Paginatable;

    /**
     * Lista asortymentu z paginacją server-side.
     *
     * Query params:
     *   - page, per_page, sort_by, sort_dir  → paginacja/sortowanie
     *   - search:    string (nazwa, opis)
     *   - type:      string (material|service)
     *   - category:  string
     *   - is_active: bool
     */
    public function index(Request $request)
    {
        try {
            $query = Assortment::query();

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sortowanie z whitelistą
            $this->applySorting($query, $request, [
                'name',
                'category',
                'type',
                'default_price',
                'created_at',
            ], 'category', 'asc');

            // Drugie sortowanie po nazwie (w ramach kategorii)
            if ($request->get('sort_by', 'category') === 'category') {
                $query->orderBy('name', 'asc');
            }

            // Paginacja
            $items = $this->paginateQuery($query, $request, 20);

            return response()->json($items);
        } catch (\Exception $e) {
            \Log::error('Assortment index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania asortymentu',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show(Assortment $assortment)
    {
        try {
            $assortment->load(['history.user']);
            return response()->json($assortment);
        } catch (\Exception $e) {
            \Log::error('Assortment show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania pozycji',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // ✅ Walidacja z Enum
                'type' => ['required', Rule::enum(AssortmentType::class)],
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'unit' => ['required', Rule::enum(AssortmentUnit::class)],
                'default_price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $item = Assortment::create($validated);

            return response()->json($item, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Assortment store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas tworzenia pozycji',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Assortment $assortment)
    {
        try {
            $validated = $request->validate([
                // ✅ Walidacja z Enum (sometimes = pole opcjonalne)
                'type' => ['sometimes', Rule::enum(AssortmentType::class)],
                'name' => 'sometimes|string|max:255',
                'category' => 'sometimes|string|max:255',
                'unit' => ['sometimes', Rule::enum(AssortmentUnit::class)],
                'default_price' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $assortment->update($validated);

            return response()->json($assortment);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Assortment update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas aktualizacji pozycji',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(Assortment $assortment)
    {
        try {
            $usageCount = DB::table('quotation_item_materials')
                ->where('assortment_item_id', $assortment->id)
                ->count();

            $usageCount += DB::table('quotation_item_services')
                ->where('assortment_item_id', $assortment->id)
                ->count();

            if ($usageCount > 0) {
                return response()->json([
                    'message' => 'Nie można usunąć pozycji, która jest używana w wycenach. Ustaw pozycję jako nieaktywną zamiast usuwania.'
                ], 400);
            }

            $assortment->delete();

            return response()->json([
                'message' => 'Pozycja usunięta pomyślnie'
            ]);
        } catch (\Exception $e) {
            \Log::error('Assortment delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas usuwania pozycji',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function materials()
    {
        $materials = Assortment::where('type', AssortmentType::MATERIAL)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($materials);
    }

    public function services()
    {
        $services = Assortment::where('type', AssortmentType::SERVICE)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($services);
    }

    public function categories(Request $request)
    {
        $type = $request->input('type');

        $query = Assortment::select('category')
            ->distinct()
            ->orderBy('category');

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->pluck('category');

        return response()->json($categories);
    }

    public function toggleActive(Assortment $assortment)
    {
        try {
            $wasActive = $assortment->is_active;
            $assortment->is_active = !$assortment->is_active;
            $assortment->save();

            // Log używając Enuma
            $action = $assortment->is_active
                ? AssortmentHistoryAction::ACTIVATED
                : AssortmentHistoryAction::DEACTIVATED;

            $description = $assortment->is_active
                ? 'Pozycja aktywowana'
                : 'Pozycja dezaktywowana';

            $assortment->logHistory(
                $action,
                ['is_active' => $wasActive],
                ['is_active' => $assortment->is_active],
                $description
            );

            return response()->json([
                'message' => 'Status zmieniony pomyślnie',
                'item' => $assortment
            ]);
        } catch (\Exception $e) {
            \Log::error('Toggle active error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas zmiany statusu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pobierz historię zmian pozycji
     */
    public function history(Assortment $assortment)
    {
        try {
            $history = $assortment->history()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($history);
        } catch (\Exception $e) {
            \Log::error('History error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania historii',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function batchCheckOrCreate(Request $request)
    {
        $request->validate([
            'type' => ['nullable', Rule::enum(AssortmentType::class)], // NOWE: Parametr typu
            'items' => 'required|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            // Unit jest opcjonalny dla usług (domyślnie 'h')
            'items.*.unit' => 'nullable|string|max:10',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $typeStr = $request->input('type', AssortmentType::MATERIAL->value);
        $assortmentType = AssortmentType::from($typeStr);

        $defaultUnit = $assortmentType === AssortmentType::SERVICE
            ? AssortmentUnit::H
            : AssortmentUnit::SZT;

        $resolvedItems = [];

        DB::transaction(function () use ($request, $assortmentType, $defaultUnit, &$resolvedItems) {
            foreach ($request->items as $itemData) {
                $importedPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : null;

                // Normalizacja jednostki
                $unitStr = $itemData['unit'] ?? $defaultUnit->value;
                $unitEnum = AssortmentUnit::tryFrom(strtoupper($unitStr)) ?? $defaultUnit;

                // Znajdź lub utwórz Asortyment
                $assortment = Assortment::firstOrCreate(
                    [
                        'name' => $itemData['name'],
                        'type' => $assortmentType
                    ],
                    [
                        'category' => 'Importowane',
                        'unit' => $unitEnum,
                        'default_price' => $importedPrice ?? 0,
                        'description' => 'Utworzono automatycznie z importu',
                        'is_active' => true
                    ]
                );

                if ($assortment->default_price == 0 && $importedPrice !== null) {
                    $assortment->update(['default_price' => $importedPrice]);
                }

                $finalUnitPrice = $importedPrice ?? $assortment->default_price;

                $resolvedItems[] = [
                    'assortment_item_id' => $assortment->id,
                    'quantity' => $itemData['quantity'], // Dla usług to będzie estimated_time_hours
                    'unit' => $assortment->unit->value,
                    'unit_price' => $finalUnitPrice,
                    'assortment_details' => $assortment
                ];
            }
        });

        return response()->json($resolvedItems);
    }

}
