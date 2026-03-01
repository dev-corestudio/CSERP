<?php

namespace App\Http\Controllers\API;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemMaterial;
use App\Models\QuotationItemService;
use App\Models\VariantMaterial;
use App\Enums\MaterialStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotationController extends Controller
{
    /**
     * Lista wycen dla wariantu
     *
     * GET /api/variants/{variant}/quotations
     */
    public function index(Variant $variant)
    {
        try {
            $quotations = $variant->quotations()
                ->with([
                    'items.materials.assortmentItem',
                    'items.services.assortmentItem',
                    'approvedBy'
                ])
                ->orderBy('version_number', 'desc')
                ->get();

            return response()->json($quotations);
        } catch (\Exception $e) {
            Log::error('Quotations index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania wycen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Szczegóły wyceny
     *
     * GET /api/quotations/{quotation}
     */
    public function show(Quotation $quotation)
    {
        try {
            $quotation->load([
                'variant.project.customer',
                'items.materials.assortmentItem',
                'items.services.assortmentItem',
                'approvedBy'
            ]);

            return response()->json($quotation);
        } catch (\Exception $e) {
            Log::error('Quotation show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Utwórz nową wycenę dla wariantu
     *
     * POST /api/variants/{variant}/quotations
     */
    public function store(Request $request, Variant $variant)
    {
        try {
            $validated = $request->validate([
                // present zamiast required — klucz musi istnieć, ale tablica może być pusta
                'materials' => 'present|array',
                'materials.*.assortment_item_id' => 'required|exists:assortment,id',
                'materials.*.quantity' => 'required|numeric|min:0.01',
                'materials.*.unit' => 'required|string|max:10',
                'materials.*.unit_price' => 'required|numeric|min:0',
                'materials.*.notes' => 'nullable|string',
                'services' => 'present|array',
                'services.*.assortment_item_id' => 'required|exists:assortment,id',
                'services.*.estimated_time_hours' => 'required|numeric|min:0',
                'services.*.unit_price' => 'required|numeric|min:0',
                'services.*.notes' => 'nullable|string',
                'margin_percent' => 'required|numeric|min:0|max:100',
                'notes' => 'nullable|string',
            ]);

            return DB::transaction(function () use ($variant, $validated) {
                $materialsCost = collect($validated['materials'])
                    ->sum(fn($m) => $m['quantity'] * $m['unit_price']);

                $servicesCost = collect($validated['services'])
                    ->sum(fn($s) => $s['estimated_time_hours'] * $s['unit_price']);

                $subtotal = $materialsCost + $servicesCost;
                $totalNet = $subtotal * (1 + $validated['margin_percent'] / 100);
                $totalGross = $totalNet * 1.23;

                $nextVersion = Quotation::where('variant_id', $variant->id)
                    ->max('version_number') + 1;

                $quotation = $variant->quotations()->create([
                    'version_number' => $nextVersion,
                    'total_materials_cost' => $materialsCost,
                    'total_services_cost' => $servicesCost,
                    'total_net' => $totalNet,
                    'total_gross' => $totalGross,
                    'margin_percent' => $validated['margin_percent'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                $quotationItem = $quotation->items()->create([
                    'materials_cost' => $materialsCost,
                    'services_cost' => $servicesCost,
                    'subtotal' => $subtotal,
                ]);

                foreach ($validated['materials'] as $material) {
                    $quotationItem->materials()->create([
                        'assortment_item_id' => $material['assortment_item_id'],
                        'quantity' => $material['quantity'],
                        'unit' => $material['unit'] ?? 'szt',
                        'unit_price' => $material['unit_price'],
                        'total_cost' => $material['quantity'] * $material['unit_price'],
                        'notes' => $material['notes'] ?? null,
                    ]);
                }

                foreach ($validated['services'] as $service) {
                    $quotationItem->services()->create([
                        'assortment_item_id' => $service['assortment_item_id'],
                        'estimated_quantity' => 1,
                        'estimated_time_hours' => $service['estimated_time_hours'],
                        'unit' => 'h',
                        'unit_price' => $service['unit_price'],
                        'total_cost' => $service['estimated_time_hours'] * $service['unit_price'],
                        'notes' => $service['notes'] ?? null,
                    ]);
                }

                $quotation->load([
                    'items.materials.assortmentItem',
                    'items.services.assortmentItem'
                ]);

                return response()->json($quotation, 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Quotation store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas tworzenia wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aktualizuj wycenę (tylko niezatwierdzone)
     *
     * PUT /api/quotations/{quotation}
     */
    public function update(Request $request, Quotation $quotation)
    {
        try {
            if ($quotation->is_approved) {
                return response()->json([
                    'message' => 'Nie można edytować zatwierdzonej wyceny'
                ], 422);
            }

            $validated = $request->validate([
                // present zamiast required — klucz musi istnieć, ale tablica może być pusta
                'materials' => 'present|array',
                'materials.*.assortment_item_id' => 'required|exists:assortment,id',
                'materials.*.quantity' => 'required|numeric|min:0.01',
                'materials.*.unit' => 'required|string|max:10',
                'materials.*.unit_price' => 'required|numeric|min:0',
                'materials.*.notes' => 'nullable|string',
                'services' => 'present|array',
                'services.*.assortment_item_id' => 'required|exists:assortment,id',
                'services.*.estimated_time_hours' => 'required|numeric|min:0',
                'services.*.unit_price' => 'required|numeric|min:0',
                'services.*.notes' => 'nullable|string',
                'margin_percent' => 'required|numeric|min:0|max:100',
                'notes' => 'nullable|string',
            ]);

            return DB::transaction(function () use ($quotation, $validated) {
                $materialsCost = collect($validated['materials'])
                    ->sum(fn($m) => $m['quantity'] * $m['unit_price']);

                $servicesCost = collect($validated['services'])
                    ->sum(fn($s) => $s['estimated_time_hours'] * $s['unit_price']);

                $subtotal = $materialsCost + $servicesCost;
                $totalNet = $subtotal * (1 + $validated['margin_percent'] / 100);
                $totalGross = $totalNet * 1.23;

                $quotation->update([
                    'total_materials_cost' => $materialsCost,
                    'total_services_cost' => $servicesCost,
                    'total_net' => $totalNet,
                    'total_gross' => $totalGross,
                    'margin_percent' => $validated['margin_percent'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                $quotation->items()->each(function ($item) {
                    $item->materials()->delete();
                    $item->services()->delete();
                    $item->delete();
                });

                $quotationItem = $quotation->items()->create([
                    'materials_cost' => $materialsCost,
                    'services_cost' => $servicesCost,
                    'subtotal' => $subtotal,
                ]);

                foreach ($validated['materials'] as $material) {
                    $quotationItem->materials()->create([
                        'assortment_item_id' => $material['assortment_item_id'],
                        'quantity' => $material['quantity'],
                        'unit' => strtoupper($material['unit'] ?? 'SZT'),
                        'unit_price' => $material['unit_price'],
                        'total_cost' => $material['quantity'] * $material['unit_price'],
                        'notes' => $material['notes'] ?? null,
                    ]);
                }

                foreach ($validated['services'] as $service) {
                    $quotationItem->services()->create([
                        'assortment_item_id' => $service['assortment_item_id'],
                        'estimated_quantity' => 1,
                        'estimated_time_hours' => $service['estimated_time_hours'],
                        'unit' => 'H',
                        'unit_price' => $service['unit_price'],
                        'total_cost' => $service['estimated_time_hours'] * $service['unit_price'],
                        'notes' => $service['notes'] ?? null,
                    ]);
                }

                return response()->json($quotation->load([
                    'items.materials.assortmentItem',
                    'items.services.assortmentItem'
                ]));
            });

        } catch (\Exception $e) {
            Log::error('Quotation update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas aktualizacji wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Zatwierdź wycenę
     *
     * PATCH /api/quotations/{quotation}/approve
     */
    public function approve(Quotation $quotation)
    {
        try {
            return DB::transaction(function () use ($quotation) {
                Quotation::where('variant_id', $quotation->variant_id)
                    ->update(['is_approved' => false]);

                $quotation->update([
                    'is_approved' => true,
                    'approved_at' => now(),
                    'approved_by_user_id' => auth()->id(),
                ]);

                // Automatycznie ustaw TKW z wyceny (koszt 1 szt.) = (mat + usł) / ilość
                $variant = $quotation->variant;
                $totalCost = $quotation->total_materials_cost + $quotation->total_services_cost;
                $quantity = max(1, (int) $variant->quantity);
                $variant->update([
                    'tkw_z_wyceny' => round($totalCost / $quantity, 2),
                ]);

                return response()->json([
                    'message' => 'Wycena zatwierdzona',
                    'quotation' => $quotation
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Quotation approve error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas zatwierdzania wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usuń wycenę
     *
     * DELETE /api/quotations/{quotation}
     */
    public function destroy(Quotation $quotation)
    {
        try {
            if ($quotation->is_approved) {
                return response()->json([
                    'message' => 'Nie można usunąć zatwierdzonej wyceny'
                ], 400);
            }

            $quotation->delete();

            return response()->json(['message' => 'Wycena usunięta pomyślnie']);
        } catch (\Exception $e) {
            Log::error('Quotation delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas usuwania wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generuj i pobierz PDF wyceny
     *
     * GET /api/quotations/{quotation}/pdf
     */
    public function downloadPdf(Quotation $quotation)
    {
        try {
            $quotation->load([
                'variant.project.customer',
                'items.materials.assortmentItem',
                'items.services.assortmentItem',
                'approvedBy'
            ]);

            $filename = 'Wycena_' . $quotation->variant->project->full_project_number
                . '_v' . $quotation->version_number . '.pdf';

            $filename = str_replace('/', '-', $filename);

            $pdf = Pdf::loadView('quotation', [
                'quotation' => $quotation,
                'project' => $quotation->variant->project,
                'customer' => $quotation->variant->project->customer,
                'variant' => $quotation->variant
            ]);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF generation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas generowania PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplikuj wycenę jako nową wersję w tym samym wariancie
     *
     * POST /api/quotations/{quotation}/duplicate
     */
    public function duplicate(Quotation $quotation)
    {
        try {
            return DB::transaction(function () use ($quotation) {
                $quotation->load(['items.materials', 'items.services']);

                $nextVersion = Quotation::where('variant_id', $quotation->variant_id)
                    ->max('version_number') + 1;

                $newQuotation = Quotation::create([
                    'variant_id' => $quotation->variant_id,
                    'version_number' => $nextVersion,
                    'total_materials_cost' => $quotation->total_materials_cost,
                    'total_services_cost' => $quotation->total_services_cost,
                    'total_net' => $quotation->total_net,
                    'total_gross' => $quotation->total_gross,
                    'margin_percent' => $quotation->margin_percent,
                    'is_approved' => false,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'notes' => $quotation->notes
                        ? '[Kopia v' . $quotation->version_number . '] ' . $quotation->notes
                        : '[Kopia v' . $quotation->version_number . ']',
                ]);

                foreach ($quotation->items as $item) {
                    $newItem = QuotationItem::create([
                        'quotation_id' => $newQuotation->id,
                        'materials_cost' => $item->materials_cost,
                        'services_cost' => $item->services_cost,
                        'subtotal' => $item->subtotal,
                    ]);

                    foreach ($item->materials as $material) {
                        QuotationItemMaterial::create([
                            'quotation_item_id' => $newItem->id,
                            'assortment_item_id' => $material->assortment_item_id,
                            'quantity' => $material->quantity,
                            'unit' => $material->unit,
                            'unit_price' => $material->unit_price,
                            'total_cost' => $material->total_cost,
                            'notes' => $material->notes,
                        ]);
                    }

                    foreach ($item->services as $service) {
                        QuotationItemService::create([
                            'quotation_item_id' => $newItem->id,
                            'assortment_item_id' => $service->assortment_item_id,
                            'estimated_quantity' => $service->estimated_quantity,
                            'estimated_time_hours' => $service->estimated_time_hours,
                            'unit' => $service->unit,
                            'unit_price' => $service->unit_price,
                            'total_cost' => $service->total_cost,
                            'notes' => $service->notes,
                        ]);
                    }
                }

                $newQuotation->load([
                    'items.materials.assortmentItem',
                    'items.services.assortmentItem',
                ]);

                Log::info("Wycena #{$quotation->id} (v{$quotation->version_number}) " .
                    "zduplikowana jako #{$newQuotation->id} (v{$nextVersion})");

                return response()->json([
                    'message' => "Wycena zduplikowana jako wersja {$nextVersion}",
                    'quotation' => $newQuotation,
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Quotation duplicate error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas duplikowania wyceny',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eksportuj materiały z zatwierdzonej wyceny do listy materiałów wariantu.
     *
     * Dostępne tryby (query param ?mode=):
     *  - skip    (domyślny) – pomija istniejące
     *  - merge   – sumuje ilości z istniejącymi
     *  - replace – nadpisuje istniejące (usuwa też duplikaty assortment_id)
     *
     * POST /api/quotations/{quotation}/export-materials?mode=skip|merge|replace
     */
    public function exportMaterials(Request $request, Quotation $quotation)
    {
        if (!$quotation->is_approved) {
            return response()->json([
                'message' => 'Można eksportować materiały tylko z zatwierdzonej wyceny',
            ], 422);
        }

        $mode = $request->query('mode', 'skip');

        if (!in_array($mode, ['skip', 'merge', 'replace'])) {
            return response()->json([
                'message' => 'Nieprawidłowy tryb. Dozwolone: skip, merge, replace',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($quotation, $mode) {
                $quotation->load('items.materials');

                // Spłaszcz materiały z wyceny do jednej kolekcji
                $sourceMaterials = $quotation->items
                    ->flatMap(fn($item) => $item->materials);

                if ($sourceMaterials->isEmpty()) {
                    return response()->json([
                        'message' => 'Wycena nie zawiera żadnych materiałów',
                    ], 422);
                }

                $variant = $quotation->variant;
                $stats = ['exported' => 0, 'skipped' => 0, 'merged' => 0, 'replaced' => 0];

                // ----------------------------------------------------------------
                // REPLACE — semantyka "zastąp całą listę":
                //   1. Usuń WSZYSTKIE materiały wariantu
                //   2. Wstaw materiały z wyceny jako nowe rekordy
                //
                // Nie ma sensu porównywać per-pozycję bo i tak czyścimy wszystko.
                // Dzięki temu wynik jest zawsze równy dokładnie temu co jest w wycenie.
                // ----------------------------------------------------------------
                if ($mode === 'replace') {
                    $deletedCount = $variant->materials()->delete();

                    foreach ($sourceMaterials as $source) {
                        VariantMaterial::create([
                            'variant_id' => $variant->id,
                            'assortment_id' => $source->assortment_item_id,
                            'quantity' => $source->quantity,
                            'unit' => $source->unit,
                            'unit_price' => $source->unit_price,
                            'total_cost' => $source->total_cost,
                            'notes' => $source->notes,
                            'status' => MaterialStatus::NOT_ORDERED,
                        ]);
                        $stats['replaced']++;
                    }

                    Log::info("Eksport materiałów z wyceny #{$quotation->id} " .
                        "do wariantu #{$variant->id}: tryb=replace, " .
                        "usunięto={$deletedCount}, wstawiono={$stats['replaced']}");

                    return response()->json([
                        'message' => $this->buildExportMessage($stats, $mode),
                        'stats' => $stats,
                    ]);
                }

                // ----------------------------------------------------------------
                // SKIP i MERGE — operują na istniejących pozycjach per assortment_id
                //
                // WAŻNE: groupBy zamiast keyBy — wariant może mieć wiele rekordów
                // z tym samym assortment_id (duplikaty). keyBy zachowałby tylko
                // ostatni, przez co wcześniejsze duplikaty nie byłyby usuwane.
                // ----------------------------------------------------------------
                $existingGroups = $variant->materials()
                    ->get()
                    ->groupBy('assortment_id');

                foreach ($sourceMaterials as $source) {
                    $assortmentId = $source->assortment_item_id;
                    $existingGroup = $existingGroups->get($assortmentId); // Collection|null

                    if ($existingGroup && $existingGroup->count() > 0) {
                        match ($mode) {

                            // POMIŃ: pozostaw istniejące bez zmian
                            'skip' => $stats['skipped']++,

                            // SCAL: zsumuj ilości ze wszystkich rekordów (łącznie z duplikatami),
                            //       zaktualizuj pierwszy, usuń duplikaty
                            'merge' => (function () use ($existingGroup, $source, &$stats) {
                                    $first = $existingGroup->first();
                                    $totalQty = (float) $existingGroup->sum('quantity')
                                    + (float) $source->quantity;

                                    $first->update([
                                    'quantity' => $totalQty,
                                    'total_cost' => $totalQty * (float) $first->unit_price,
                                    ]);

                                    foreach ($existingGroup->slice(1) as $dup) {
                                        $dup->delete();
                                    }

                                    $stats['merged']++;
                                })(),

                            // (replace obsługiwany osobno powyżej — ta gałąź nie powinna wystąpić)
                            default => null,
                        };
                    } else {
                        // Nowy materiał — utwórz w wariancie
                        VariantMaterial::create([
                            'variant_id' => $variant->id,
                            'assortment_id' => $assortmentId,
                            'quantity' => $source->quantity,
                            'unit' => $source->unit,
                            'unit_price' => $source->unit_price,
                            'total_cost' => $source->total_cost,
                            'notes' => $source->notes,
                            'status' => MaterialStatus::NOT_ORDERED,
                        ]);
                        $stats['exported']++;
                    }
                }

                Log::info("Eksport materiałów z wyceny #{$quotation->id} " .
                    "do wariantu #{$variant->id}: tryb={$mode}, " . json_encode($stats));

                return response()->json([
                    'message' => $this->buildExportMessage($stats, $mode),
                    'stats' => $stats,
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Eksport materiałów z wyceny error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas eksportu materiałów',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Zbuduj czytelny komunikat podsumowujący eksport
     */
    private function buildExportMessage(array $stats, string $mode): string
    {
        $parts = [];

        if ($stats['exported'] > 0) {
            $parts[] = "dodano {$stats['exported']} nowych";
        }
        if ($stats['merged'] > 0) {
            $parts[] = "scalono ilości w {$stats['merged']}";
        }
        if ($stats['replaced'] > 0) {
            $parts[] = "zastąpiono {$stats['replaced']}";
        }
        if ($stats['skipped'] > 0) {
            $parts[] = "pominięto {$stats['skipped']} istniejących";
        }

        if (empty($parts)) {
            return 'Brak materiałów do eksportu.';
        }

        return 'Eksport zakończony: ' . implode(', ', $parts) . '.';
    }
}
