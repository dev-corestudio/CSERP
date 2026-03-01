<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\ProductionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectOverallStatus;
use App\Enums\ProjectPriority;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use Paginatable;

    // =========================================================================
    // NUMER PROJEKTU
    // =========================================================================

    /**
     * Zwraca następny wolny numer projektu (podgląd).
     * Używany przez frontend do wyświetlenia info — nie rezerwuje numeru.
     *
     * GET /api/projects/next-number
     */
    public function nextNumber()
    {
        $maxNumber = Project::max('project_number');
        $next = $maxNumber ? intval($maxNumber) + 1 : 1000;
        $formatted = str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        return response()->json(['next_number' => $formatted]);
    }

    // =========================================================================
    // LISTA PROJEKTÓW
    // =========================================================================

    /**
     * Lista projektów z paginacją server-side.
     *
     * Query params:
     *   - page:         int    (domyślnie 1)
     *   - per_page:     int    (domyślnie 15, max 100)
     *   - sort_by:      string (created_at | project_number | planned_delivery_date | overall_status)
     *   - sort_dir:     string (asc | desc, domyślnie desc)
     *   - search:       string (szuka w project_number, description, customer.name)
     *   - status:       string (filtruje overall_status)
     *   - quick_filter: string (active | completed | all)
     *
     * GET /api/projects
     */
    public function index(Request $request)
    {
        try {
            $query = Project::with(['customer', 'variants', 'assignedUser']);

            // Filtr statusu
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('overall_status', $request->input('status'));
            }

            // Quick filter (aktywne/zakończone)
            if ($request->filled('quick_filter')) {
                $quickFilter = $request->input('quick_filter');

                if ($quickFilter === 'active') {
                    $query->whereIn('overall_status', [
                        'draft',
                        'quotation',
                        'prototype',
                        'production',
                        'delivery',
                        'DRAFT',
                        'QUOTATION',
                        'PROTOTYPE',
                        'PRODUCTION',
                        'DELIVERY',
                    ]);
                } elseif ($quickFilter === 'completed') {
                    $query->whereIn('overall_status', [
                        'completed',
                        'cancelled',
                        'COMPLETED',
                        'CANCELLED',
                    ]);
                }
                // 'all' — brak dodatkowego filtra
            }

            // Wyszukiwanie
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('project_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sortowanie z whitelistą kolumn
            $this->applySorting($query, $request, [
                'created_at',
                'project_number',
                'planned_delivery_date',
                'overall_status',
            ], 'created_at', 'desc');

            // Paginacja
            $projects = $this->paginateQuery($query, $request);

            return response()->json($projects);
        } catch (\Exception $e) {
            \Log::error('Projects index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania projektów',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // SZCZEGÓŁY PROJEKTU
    // =========================================================================

    /**
     * Szczegóły projektu z wariantami, prototypami i wycenami.
     *
     * GET /api/projects/{project}
     */
    public function show(Project $project)
    {
        try {
            $project->load([
                'customer',
                'assignedUser',
                'variants.prototypes',
                'variants.productionOrder',
                'variants.approvedQuotation',
                'images'
            ]);

            return response()->json($project);
        } catch (\Exception $e) {
            \Log::error('Project show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania projektu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // TWORZENIE NOWEGO PROJEKTU
    // =========================================================================

    /**
     * Utwórz nowy projekt.
     *
     * Numer projektu (project_number) jest ZAWSZE generowany przez serwer.
     * Nowy projekt dostaje serię 0001.
     *
     * Body:
     * {
     *   "customer_id":           1,           (wymagane)
     *   "description":           "...",        (wymagane)
     *   "planned_delivery_date": "2025-12-01", (wymagane)
     *   "priority":              "normal"      (opcjonalne, domyślnie "normal")
     * }
     *
     * POST /api/projects
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id'            => 'required|exists:customers,id',
                'description'            => 'required|string',
                'planned_delivery_date'  => 'required|date',
                'priority'               => ['nullable', Rule::enum(ProjectPriority::class)],
                'assigned_to'            => 'nullable|exists:users,id',
            ]);

            DB::beginTransaction();

            // Serwer generuje kolejny numer projektu
            $maxNumber = Project::max('project_number');
            $nextNumber = $maxNumber ? intval($maxNumber) + 1 : 1000;
            $projectNumber = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            // Pierwszy projekt z tym numerem → seria 0001
            $series = Project::generateSeries($projectNumber);

            // Opiekun: jawnie podany → opiekun klienta → zalogowany użytkownik
            $assignedTo = $validated['assigned_to']
                ?? Customer::find($validated['customer_id'])?->assigned_to
                ?? $request->user()->id;

            $project = Project::create([
                'customer_id'           => $validated['customer_id'],
                'assigned_to'           => $assignedTo,
                'project_number'        => $projectNumber,
                'series'                => $series,
                'description'           => $validated['description'],
                'planned_delivery_date' => $validated['planned_delivery_date'],
                'priority'              => $validated['priority'] ?? ProjectPriority::NORMAL,
                'overall_status'        => ProjectOverallStatus::DRAFT,
                'payment_status'        => PaymentStatus::UNPAID,
            ]);

            DB::commit();

            $project->load(['customer', 'assignedUser']);

            return response()->json($project, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Project store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas tworzenia projektu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // AKTUALIZACJA PROJEKTU
    // =========================================================================

    /**
     * Aktualizuj dane projektu.
     * Nie można zmieniać project_number ani series przez ten endpoint.
     *
     * PUT /api/projects/{project}
     */
    public function update(Request $request, Project $project)
    {
        try {
            $validated = $request->validate([
                'customer_id'           => 'sometimes|exists:customers,id',
                'assigned_to'           => 'sometimes|nullable|exists:users,id',
                'description'           => 'sometimes|string',
                'planned_delivery_date' => 'sometimes|date',
                'priority'              => ['sometimes', Rule::enum(ProjectPriority::class)],
                'overall_status'        => 'sometimes|string',
            ]);

            // project_number i series są niezmieniane — integralność danych
            $project->update($validated);
            $project->load(['customer', 'assignedUser']);

            return response()->json($project);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Project update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas aktualizacji projektu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // USUWANIE PROJEKTU
    // =========================================================================

    /**
     * Usuń projekt.
     * Dozwolone tylko w statusie draft lub quotation (brak aktywnej produkcji).
     *
     * DELETE /api/projects/{project}
     */
    public function destroy(Project $project)
    {
        try {
            if (!in_array($project->overall_status->value ?? $project->overall_status, ['draft', 'quotation', 'DRAFT', 'QUOTATION'])) {
                return response()->json([
                    'message' => 'Można usunąć tylko projekty w fazie szkicu lub wyceny'
                ], 403);
            }

            $projectNumber = $project->full_project_number;
            $project->delete();

            return response()->json([
                'message' => "Projekt {$projectNumber} usunięty pomyślnie"
            ]);
        } catch (\Exception $e) {
            \Log::error('Project delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas usuwania projektu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // PODSUMOWANIE FINANSOWE
    // =========================================================================

    /**
     * Pełne podsumowanie finansowe projektu.
     *
     * GET /api/projects/{project}/financial-summary
     */
    public function financialSummary(Project $project)
    {
        try {
            $project->load([
                'variants.approvedQuotation',
                'variants.productionOrder.services',
            ]);

            $variantsSummary = [];
            $totalApprovedNet = 0.0;
            $totalApprovedGross = 0.0;
            $totalApprovedMat = 0.0;
            $totalApprovedSvc = 0.0;
            $totalActualMat = 0.0;
            $totalActualSvc = 0.0;

            foreach ($project->variants as $variant) {
                $approvedQ = $variant->approvedQuotation;

                $approvedNet = $approvedQ ? (float) $approvedQ->total_net : 0.0;
                $approvedGross = $approvedQ ? (float) $approvedQ->total_gross : 0.0;
                $approvedMat = $approvedQ ? (float) $approvedQ->total_materials_cost : 0.0;
                $approvedSvc = $approvedQ ? (float) $approvedQ->total_services_cost : 0.0;

                // Koszty rzeczywiste materiałów
                $actualMat = (float) \App\Models\VariantMaterial::where('variant_id', $variant->id)
                    ->sum('total_cost');

                // Koszty rzeczywiste usług — tylko COMPLETED i IN_PROGRESS
                $actualSvc = 0.0;
                if ($variant->productionOrder) {
                    $actualSvc = (float) \App\Models\ProductionService::where('production_order_id', $variant->productionOrder->id)
                        ->whereIn('status', [
                            ProductionStatus::COMPLETED->value,
                            ProductionStatus::IN_PROGRESS->value,
                        ])
                        ->whereNotNull('actual_cost')
                        ->sum('actual_cost');
                }

                $variantsSummary[] = [
                    'variant_id' => $variant->id,
                    'variant_number' => $variant->variant_number,
                    'variant_name' => $variant->name,
                    'has_approved_quotation' => (bool) $approvedQ,
                    'approved_net' => round($approvedNet, 2),
                    'approved_gross' => round($approvedGross, 2),
                    'approved_materials_cost' => round($approvedMat, 2),
                    'approved_services_cost' => round($approvedSvc, 2),
                    'actual_materials_cost' => round($actualMat, 2),
                    'actual_services_cost' => round($actualSvc, 2),
                    'actual_total' => round($actualMat + $actualSvc, 2),
                    'variance' => round(($actualMat + $actualSvc) - $approvedGross, 2),
                ];

                $totalApprovedNet += $approvedNet;
                $totalApprovedGross += $approvedGross;
                $totalApprovedMat += $approvedMat;
                $totalApprovedSvc += $approvedSvc;
                $totalActualMat += $actualMat;
                $totalActualSvc += $actualSvc;
            }

            $totalActual = $totalActualMat + $totalActualSvc;
            $totalVariance = $totalActual - $totalApprovedGross;

            return response()->json([
                'project_id' => $project->id,
                'project_number' => $project->full_project_number,

                'total_approved_net' => round($totalApprovedNet, 2),
                'total_approved_gross' => round($totalApprovedGross, 2),
                'total_approved_materials' => round($totalApprovedMat, 2),
                'total_approved_services' => round($totalApprovedSvc, 2),

                'total_actual_materials' => round($totalActualMat, 2),
                'total_actual_services' => round($totalActualSvc, 2),
                'total_actual' => round($totalActual, 2),

                'total_variance' => round($totalVariance, 2),
                'variance_percent' => $totalApprovedGross > 0
                    ? round(($totalVariance / $totalApprovedGross) * 100, 2)
                    : null,

                'variants' => $variantsSummary,
            ]);

        } catch (\Exception $e) {
            \Log::error('Project financial summary error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania podsumowania finansowego',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
