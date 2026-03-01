<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\ProductionOrder;
use App\Models\ProductionService;
use App\Enums\ProductionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductionController extends Controller
{
    /**
     * Pobierz zlecenie produkcyjne wariantu (lub null jeśli nie istnieje).
     *
     * GET /api/variants/{variant}/production
     */
    public function show(Variant $variant): JsonResponse
    {
        $production = $variant->productionOrder()
            ->with(['services.workstation', 'services.assignedWorker'])
            ->first();

        if (!$production) {
            return response()->json(null);
        }

        return response()->json($production);
    }

    /**
     * Utwórz zlecenie produkcyjne dla wariantu.
     *
     * POST /api/variants/{variant}/production
     */
    public function store(Request $request, Variant $variant): JsonResponse
    {
        if ($variant->productionOrder) {
            return response()->json([
                'message' => 'Zlecenie produkcyjne dla tego wariantu już istnieje.',
            ], 422);
        }

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $production = $variant->productionOrder()->create([
            'quantity' => $validated['quantity'] ?? $variant->quantity,
            'total_estimated_cost' => 0,
            'total_actual_cost' => 0,
            'status' => ProductionStatus::PLANNED,
        ]);

        return response()->json($production->load(['services']), 201);
    }

    /**
     * Szczegóły zlecenia produkcyjnego.
     *
     * GET /api/production/{production}
     */
    public function details(ProductionOrder $production): JsonResponse
    {
        $production->load([
            'variant.project.customer',
            'services.workstation',
            'services.assignedWorker',
        ]);

        return response()->json($production);
    }

    /**
     * Aktualizuj zlecenie produkcyjne.
     *
     * PUT /api/production/{production}
     */
    public function update(Request $request, ProductionOrder $production): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'status' => ['sometimes', Rule::enum(ProductionStatus::class)],
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        $production->update($validated);

        return response()->json($production->load(['services.workstation', 'services.assignedWorker']));
    }

    /**
     * Lista usług produkcyjnych zlecenia.
     *
     * GET /api/production/{production}/services
     */
    public function services(ProductionOrder $production): JsonResponse
    {
        $services = $production->services()
            ->with(['workstation', 'assignedWorker'])
            ->orderBy('step_number')
            ->get();

        return response()->json($services);
    }

    /**
     * Dodaj usługę do zlecenia produkcyjnego.
     *
     * POST /api/production/{production}/services
     */
    public function addService(Request $request, ProductionOrder $production): JsonResponse
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

        $nextStep = $production->services()->max('step_number') + 1;
        $estimatedQty = $validated['estimated_quantity'] ?? 1;
        $estimatedCost = $estimatedQty * $validated['unit_price'];

        $service = $production->services()->create([
            'step_number' => $nextStep,
            'service_name' => $validated['service_name'],
            'workstation_id' => $validated['workstation_id'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'estimated_quantity' => $estimatedQty,
            'estimated_time_hours' => $validated['estimated_time_hours'],
            'unit_price' => $validated['unit_price'],
            'estimated_cost' => $estimatedCost,
            'status' => ProductionStatus::PLANNED,
            'worker_notes' => $validated['worker_notes'] ?? null,
        ]);

        $service->load(['workstation', 'assignedWorker']);

        return response()->json($service, 201);
    }

    /**
     * Aktualizuj usługę produkcyjną.
     *
     * PUT /api/production-services/{service}
     */
    public function updateService(Request $request, ProductionService $service): JsonResponse
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
            'status' => ['sometimes', Rule::enum(ProductionStatus::class)],
            'worker_notes' => 'nullable|string',
        ]);

        $quantity = $validated['estimated_quantity'] ?? $service->estimated_quantity;
        $unitPrice = $validated['unit_price'] ?? $service->unit_price;
        $validated['estimated_cost'] = $quantity * $unitPrice;

        $service->update($validated);
        $service->load(['workstation', 'assignedWorker']);

        return response()->json($service);
    }

    /**
     * Usuń usługę produkcyjną (tylko zaplanowane).
     *
     * DELETE /api/production-services/{service}
     */
    public function deleteService(ProductionService $service): JsonResponse
    {
        if ($service->status !== ProductionStatus::PLANNED) {
            return response()->json([
                'message' => 'Można usunąć tylko zaplanowane zadania produkcyjne.',
            ], 400);
        }

        $service->delete();

        return response()->json(['message' => 'Usługa produkcyjna usunięta pomyślnie']);
    }
}
