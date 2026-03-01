<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Variant;
use App\Services\SeriesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * ProjectSeriesController — zarządzanie seriami projektów
 *
 * Seria = kolejne uruchomienie produkcji dla tego samego numeru projektu.
 *
 * Endpointy:
 *   GET  /api/projects/{project}/series          → lista wszystkich serii dla project_number
 *   GET  /api/projects/{project}/series/variants  → warianty z serii do selektora kopiowania
 *   POST /api/projects/{project}/series/create    → utwórz nową serię (pusta lub z kopiowaniem)
 */
class ProjectSeriesController extends Controller
{
    public function __construct(
        protected SeriesService $seriesService
    ) {
    }

    // =========================================================================
    // LISTA SERII
    // =========================================================================

    /**
     * Pobierz wszystkie serie dla danego numeru projektu.
     *
     * GET /api/projects/{project}/series
     */
    public function index(Project $project): JsonResponse
    {
        try {
            $series = $this->seriesService->getAllSeriesForProjectNumber($project->project_number);

            return response()->json([
                'project_number' => $project->project_number,
                'data' => $series,
                'count' => $series->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("ProjectSeriesController::index error: {$e->getMessage()}");
            return response()->json([
                'message' => 'Błąd podczas pobierania serii projektu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SELEKTOR WARIANTÓW DO KOPIOWANIA
    // =========================================================================

    /**
     * Pobierz warianty z danej serii do wyświetlenia w selektorze kopiowania.
     *
     * GET /api/projects/{project}/series/variants
     */
    public function variantsForSelector(Project $project): JsonResponse
    {
        try {
            $variants = $this->seriesService->getVariantsForCopySelector($project);

            return response()->json([
                'project_id' => $project->id,
                'full_project_number' => $project->full_project_number,
                'data' => $variants,
            ]);
        } catch (\Exception $e) {
            Log::error("ProjectSeriesController::variantsForSelector error: {$e->getMessage()}");
            return response()->json([
                'message' => 'Błąd podczas pobierania wariantów do selektora',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // TWORZENIE NOWEJ SERII
    // =========================================================================

    /**
     * Utwórz nową serię dla projektu.
     *
     * POST /api/projects/{project}/series/create
     */
    public function create(Request $request, Project $project): JsonResponse
    {
        // Walidacja danych wejściowych
        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'planned_delivery_date' => 'nullable|date',
            'priority' => 'nullable|string|in:low,normal,high,urgent',

            // Opcjonalne — tylko gdy kopiujemy z innej serii
            'copy_from_project_id' => 'nullable|integer|exists:projects,id',

            // Lista wariantów do skopiowania
            'variants' => 'nullable|array',
            'variants.*.source_variant_id' => [
                'required_with:variants',
                'integer',
                'exists:variants,id',
            ],
            'variants.*.copy_quotation' => 'nullable|boolean',
            'variants.*.copy_materials' => 'nullable|boolean',
        ]);

        try {
            // Jeśli podano copy_from_project_id — sprawdź czy ma ten sam project_number
            $sourceForCopy = null;
            if (!empty($validated['copy_from_project_id'])) {
                $sourceForCopy = Project::findOrFail($validated['copy_from_project_id']);

                if ($sourceForCopy->project_number !== $project->project_number) {
                    return response()->json([
                        'message' => "Seria źródłowa (#{$sourceForCopy->id}, " .
                            "{$sourceForCopy->full_project_number}) ma inny numer projektu " .
                            "niż {$project->full_project_number}. " .
                            "Można kopiować tylko z tej samej grupy serii.",
                        'errors' => [
                            'copy_from_project_id' => [
                                'Seria źródłowa musi mieć ten sam numer projektu.'
                            ]
                        ]
                    ], 422);
                }

                // Sprawdź czy wszystkie warianty do skopiowania należą do copy_from_project_id
                if (!empty($validated['variants'])) {
                    $sourceVariantIds = Variant::where('project_id', $sourceForCopy->id)
                        ->pluck('id')
                        ->toArray();

                    foreach ($validated['variants'] as $variantConfig) {
                        if (!in_array($variantConfig['source_variant_id'], $sourceVariantIds)) {
                            return response()->json([
                                'message' => "Wariant #{$variantConfig['source_variant_id']} " .
                                    "nie należy do serii #{$sourceForCopy->id} " .
                                    "({$sourceForCopy->full_project_number}).",
                                'errors' => [
                                    'variants' => [
                                        "Wariant #{$variantConfig['source_variant_id']} " .
                                        "nie należy do wybranej serii źródłowej."
                                    ]
                                ]
                            ], 422);
                        }
                    }
                }
            }

            // Dane nowego projektu
            $projectData = [
                'description' => $validated['description'],
                'planned_delivery_date' => $validated['planned_delivery_date'] ?? null,
                'priority' => $validated['priority'] ?? 'normal',
            ];

            // Warianty do skopiowania (null = pusta seria)
            $variantsToCopy = !empty($validated['variants']) ? $validated['variants'] : null;

            // Tworzymy nową serię
            $newProject = $this->seriesService->createNewSeries(
                sourceProject: $project,
                projectData: $projectData,
                variantsToCopy: $variantsToCopy
            );

            // Przygotuj opis tego co zostało zrobione
            $summary = $this->buildCreationSummary($newProject, $variantsToCopy, $sourceForCopy);

            return response()->json([
                'message' => "Nowa seria {$newProject->full_project_number} utworzona pomyślnie.",
                'data' => $newProject,
                'summary' => $summary,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("ProjectSeriesController::create error: {$e->getMessage()}", [
                'project_id' => $project->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Błąd podczas tworzenia nowej serii',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // POMOCNICZE
    // =========================================================================

    private function buildCreationSummary(
        Project $newProject,
        ?array $variantsToCopy,
        ?Project $sourceProject
    ): array {
        $summary = [
            'new_project_id' => $newProject->id,
            'new_full_project_number' => $newProject->full_project_number,
            'variants_created' => $newProject->variants->count(),
            'copied_from' => $sourceProject?->full_project_number,
        ];

        if (!empty($variantsToCopy)) {
            $withQuotation = collect($variantsToCopy)->where('copy_quotation', true)->count();
            $withMaterials = collect($variantsToCopy)->where('copy_materials', true)->count();

            $summary['copy_details'] = [
                'variants_requested' => count($variantsToCopy),
                'with_quotation_copy' => $withQuotation,
                'with_materials_copy' => $withMaterials,
            ];
        }

        return $summary;
    }
}
