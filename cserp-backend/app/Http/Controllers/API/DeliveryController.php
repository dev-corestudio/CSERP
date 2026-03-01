<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Delivery;
use App\Enums\DeliveryStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    /**
     * Lista dostaw dla wariantu.
     *
     * GET /api/variants/{variant}/deliveries
     */
    public function index(Variant $variant): JsonResponse
    {
        $deliveries = $variant->deliveries()
            ->orderBy('delivery_date', 'desc')
            ->get();

        return response()->json($deliveries);
    }

    /**
     * Utwórz dostawę dla wariantu.
     *
     * POST /api/variants/{variant}/deliveries
     */
    public function store(Request $request, Variant $variant): JsonResponse
    {
        $validated = $request->validate([
            'delivery_number' => 'nullable|string|max:255',
            'delivery_date' => 'required|date',
            'tracking_number' => 'nullable|string|max:255',
            'courier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = DeliveryStatus::SCHEDULED;

        $delivery = $variant->deliveries()->create($validated);

        return response()->json($delivery, 201);
    }

    /**
     * Szczegóły dostawy.
     *
     * GET /api/deliveries/{delivery}
     */
    public function show(Delivery $delivery): JsonResponse
    {
        $delivery->load(['variant.project.customer']);

        return response()->json($delivery);
    }

    /**
     * Aktualizuj dostawę.
     *
     * PUT /api/deliveries/{delivery}
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        $validated = $request->validate([
            'delivery_number' => 'nullable|string|max:255',
            'delivery_date' => 'sometimes|date',
            'tracking_number' => 'nullable|string|max:255',
            'courier' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::enum(DeliveryStatus::class)],
            'notes' => 'nullable|string',
        ]);

        $delivery->update($validated);

        return response()->json($delivery);
    }

    /**
     * Oznacz dostawę jako zrealizowaną.
     *
     * PATCH /api/deliveries/{delivery}/complete
     */
    public function complete(Delivery $delivery): JsonResponse
    {
        if ($delivery->status === DeliveryStatus::DELIVERED) {
            return response()->json([
                'message' => 'Dostawa jest już oznaczona jako zrealizowana.',
            ], 422);
        }

        $delivery->update([
            'status' => DeliveryStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        return response()->json($delivery);
    }

    /**
     * Usuń dostawę (tylko zaplanowane).
     *
     * DELETE /api/deliveries/{delivery}
     */
    public function destroy(Delivery $delivery): JsonResponse
    {
        if ($delivery->status === DeliveryStatus::DELIVERED) {
            return response()->json([
                'message' => 'Nie można usunąć zrealizowanej dostawy.',
            ], 400);
        }

        $delivery->delete();

        return response()->json(['message' => 'Dostawa usunięta pomyślnie']);
    }
}
