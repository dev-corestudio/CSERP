<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prototype;
use App\Models\PrototypeMaterial;
use App\Enums\MaterialStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PrototypeMaterialController extends Controller
{
    /**
     * Lista materiałów prototypu
     *
     * GET /api/prototypes/{prototype}/materials
     */
    public function index(Prototype $prototype): JsonResponse
    {
        $materials = $prototype->materials()
            ->with('assortment')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $materials,
            'summary' => $prototype->materials_status_summary,
            'total_cost' => $prototype->total_materials_cost,
        ]);
    }

    /**
     * Dodaj materiał do prototypu
     *
     * POST /api/prototypes/{prototype}/materials
     */
    public function store(Request $request, Prototype $prototype): JsonResponse
    {
        $validated = $request->validate([
            'assortment_id' => 'required|exists:assortment,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:10',
            'unit_price' => 'required|numeric|min:0',
            'status' => ['nullable', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'quantity_in_stock' => 'nullable|numeric|min:0',
            'quantity_ordered' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['total_cost'] = $validated['quantity'] * $validated['unit_price'];
        $validated['status'] = $validated['status'] ?? MaterialStatus::NOT_ORDERED->value;

        $material = $prototype->materials()->create($validated);
        $material->load('assortment');

        return response()->json($material, 201);
    }

    /**
     * Szczegóły materiału prototypu
     *
     * GET /api/prototype-materials/{material}
     */
    public function show(PrototypeMaterial $material): JsonResponse
    {
        $material->load('assortment', 'prototype');
        return response()->json($material);
    }

    /**
     * Aktualizuj materiał prototypu
     *
     * PUT /api/prototype-materials/{material}
     */
    public function update(Request $request, PrototypeMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'assortment_id' => 'sometimes|exists:assortment,id',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit' => 'sometimes|string|max:10',
            'unit_price' => 'sometimes|numeric|min:0',
            'status' => ['sometimes', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'received_at' => 'nullable|date',
            'quantity_in_stock' => 'sometimes|numeric|min:0',
            'quantity_ordered' => 'sometimes|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $quantity = $validated['quantity'] ?? $material->quantity;
        $unitPrice = $validated['unit_price'] ?? $material->unit_price;
        $validated['total_cost'] = $quantity * $unitPrice;

        $material->update($validated);
        $material->load('assortment');

        return response()->json($material);
    }

    /**
     * Usuń materiał prototypu
     *
     * DELETE /api/prototype-materials/{material}
     */
    public function destroy(PrototypeMaterial $material): JsonResponse
    {
        $material->delete();

        return response()->json([
            'message' => 'Materiał prototypu usunięty pomyślnie'
        ]);
    }

    /**
     * Zmień status materiału prototypu (szybka akcja)
     *
     * PATCH /api/prototype-materials/{material}/status
     */
    public function updateStatus(Request $request, PrototypeMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'received_at' => 'nullable|date',
            'quantity_in_stock' => 'nullable|numeric|min:0',
            'quantity_ordered' => 'nullable|numeric|min:0',
        ]);

        $status = MaterialStatus::from($validated['status']);

        if ($status === MaterialStatus::ORDERED && !isset($validated['ordered_at'])) {
            $validated['ordered_at'] = now()->toDateString();
        }

        if ($status === MaterialStatus::IN_STOCK && !isset($validated['received_at'])) {
            $validated['received_at'] = now()->toDateString();
            $validated['quantity_in_stock'] = $material->quantity;
        }

        $material->update($validated);
        $material->load('assortment');

        return response()->json($material);
    }
}
