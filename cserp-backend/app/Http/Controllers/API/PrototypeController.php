<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Prototype;
use App\Models\PrototypeService;
use App\Enums\ProductionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PrototypeController extends Controller
{
    /**
     * Lista prototypów wariantu z materiałami i usługami RCP
     *
     * GET /api/variants/{variant}/prototypes
     */
    public function index(Variant $variant): JsonResponse
    {
        $prototypes = $variant->prototypes()
            ->with([
                'materials.assortment',
                'services.workstation',
                'services.assignedWorker',
            ])
            ->orderBy('version_number', 'desc')
            ->get();

        return response()->json($prototypes);
    }

    /**
     * Szczegóły prototypu z materiałami i usługami RCP
     *
     * GET /api/prototypes/{prototype}
     */
    public function show(Prototype $prototype): JsonResponse
    {
        $prototype->load([
            'variant.order.customer',
            'materials.assortment',
            'services.workstation',
            'services.assignedWorker',
        ]);

        return response()->json([
            'data' => $prototype,
            'materials_summary' => $prototype->materials_status_summary,
            'total_materials_cost' => $prototype->total_materials_cost,
            'total_services_cost' => $prototype->total_services_cost,
            'total_cost' => $prototype->total_cost,
        ]);
    }

    /**
     * Utwórz nowy prototyp
     *
     * POST /api/variants/{variant}/prototypes
     */
    public function store(Request $request, Variant $variant): JsonResponse
    {
        $validated = $request->validate([
            'feedback_notes' => 'nullable|string',
        ]);

        // Pobierz następny numer wersji
        $nextVersion = $variant->prototypes()->max('version_number') + 1;

        $prototype = $variant->prototypes()->create([
            'version_number' => $nextVersion,
            'test_result' => 'PENDING',
            'feedback_notes' => $validated['feedback_notes'] ?? null,
            'sent_to_client_date' => now(),
        ]);

        $prototype->load(['materials.assortment', 'services']);

        return response()->json($prototype, 201);
    }

    /**
     * Aktualizuj prototyp
     *
     * PUT /api/prototypes/{prototype}
     */
    public function update(Request $request, Prototype $prototype): JsonResponse
    {
        $validated = $request->validate([
            'test_result' => 'sometimes|in:PENDING,PASSED,FAILED',
            'feedback_notes' => 'nullable|string',
            'sent_to_client_date' => 'nullable|date',
            'client_response_date' => 'nullable|date',
        ]);

        $prototype->update($validated);

        return response()->json($prototype);
    }

    /**
     * Zatwierdź prototyp (jeden zatwierdzony na wariant)
     *
     * PATCH /api/prototypes/{prototype}/approve
     */
    public function approve(Prototype $prototype): JsonResponse
    {
        return DB::transaction(function () use ($prototype) {
            // Odznacz wszystkie inne prototypy tego wariantu
            Prototype::where('variant_id', $prototype->variant_id)
                ->update(['is_approved' => false]);

            // Zatwierdź ten prototyp
            $prototype->update([
                'is_approved' => true,
                'test_result' => 'PASSED',
                'client_response_date' => now(),
            ]);

            // Zaktualizuj wariant — ustaw zatwierdzony prototyp
            $prototype->variant->update([
                'approved_prototype_id' => $prototype->id,
            ]);

            $prototype->load(['materials.assortment', 'services']);

            return response()->json($prototype);
        });
    }

    /**
     * Odrzuć prototyp
     *
     * PATCH /api/prototypes/{prototype}/reject
     */
    public function reject(Request $request, Prototype $prototype): JsonResponse
    {
        $validated = $request->validate([
            'feedback_notes' => 'nullable|string',
        ]);

        $prototype->update([
            'test_result' => 'FAILED',
            'client_response_date' => now(),
            'feedback_notes' => $validated['feedback_notes'] ?? $prototype->feedback_notes,
        ]);

        return response()->json($prototype);
    }

    // =========================================================================
    // USŁUGI RCP PROTOTYPU (oddzielne od production_services)
    // =========================================================================

    /**
     * Lista zadań RCP prototypu
     *
     * GET /api/prototypes/{prototype}/services
     */
    public function services(Prototype $prototype): JsonResponse
    {
        $services = $prototype->services()
            ->with(['workstation', 'assignedWorker'])
            ->orderBy('step_number')
            ->get();

        return response()->json($services);
    }

    /**
     * Dodaj zadanie RCP do prototypu
     *
     * POST /api/prototypes/{prototype}/services
     */
    public function storeService(Request $request, Prototype $prototype): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'workstation_id' => 'nullable|exists:workstations,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'estimated_quantity' => 'sometimes|numeric|min:0',
            'estimated_time_hours' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'worker_notes' => 'nullable|string',
        ]);

        // Następny numer kroku
        $nextStep = $prototype->services()->max('step_number') + 1;

        $validated['step_number'] = $nextStep;
        $validated['estimated_quantity'] = $validated['estimated_quantity'] ?? 1;
        $validated['estimated_cost'] = ($validated['estimated_quantity'] ?? 1) * $validated['unit_price'];

        $service = $prototype->services()->create($validated);
        $service->load(['workstation', 'assignedWorker']);

        return response()->json($service, 201);
    }

    /**
     * Aktualizuj zadanie RCP prototypu
     *
     * PUT /api/prototype-services/{service}
     */
    public function updateService(Request $request, PrototypeService $service): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => 'sometimes|string|max:255',
            'workstation_id' => 'nullable|exists:workstations,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'estimated_quantity' => 'sometimes|numeric|min:0',
            'estimated_time_hours' => 'sometimes|numeric|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'actual_quantity' => 'nullable|numeric|min:0',
            'actual_time_hours' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:PLANNED,IN_PROGRESS,PAUSED,COMPLETED,CANCELLED',
            'worker_notes' => 'nullable|string',
        ]);

        // Przelicz szacowany koszt jeśli zmieniono dane
        $quantity = $validated['estimated_quantity'] ?? $service->estimated_quantity;
        $unitPrice = $validated['unit_price'] ?? $service->unit_price;
        $validated['estimated_cost'] = $quantity * $unitPrice;

        $service->update($validated);
        $service->load(['workstation', 'assignedWorker']);

        return response()->json($service);
    }

    /**
     * Usuń zadanie RCP prototypu
     *
     * DELETE /api/prototype-services/{service}
     */
    public function destroyService(PrototypeService $service): JsonResponse
    {
        if ($service->status !== ProductionStatus::PLANNED) {
            return response()->json([
                'message' => 'Można usunąć tylko zaplanowane zadania'
            ], 400);
        }

        $service->delete();

        return response()->json([
            'message' => 'Zadanie RCP prototypu usunięte pomyślnie'
        ]);
    }
}
