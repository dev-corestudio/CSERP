<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Workstation;
use App\Models\User;
use App\Enums\WorkstationType;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Enums\WorkstationStatus;

class WorkstationController extends Controller
{
    /**
     * Lista stanowisk z operatorami i aktualnym zadaniem.
     */
    public function index()
    {
        $workstations = Workstation::with(['operators', 'currentTask.productionOrder.variant'])
            ->orderBy('name')
            ->get();

        return response()->json($workstations);
    }

    /**
     * Tworzenie nowego stanowiska.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:workstations,name|max:255',
            'type' => ['required', Rule::enum(WorkstationType::class)],
            'location' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(WorkstationStatus::class)],
            'operator_ids' => 'nullable|array',
            'operator_ids.*' => 'exists:users,id'
        ]);

        $workstation = Workstation::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'location' => $validated['location'],
            'status' => $validated['status'],
        ]);

        if (isset($validated['operator_ids'])) {
            $workstation->operators()->sync($validated['operator_ids']);
        }

        return response()->json($workstation->load('operators'), 201);
    }

    /**
     * Szczegóły stanowiska.
     */
    public function show(Workstation $workstation)
    {
        $workstation->load([
            'operators',
            'tasks' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        return response()->json($workstation);
    }

    /**
     * Aktualizacja stanowiska.
     */
    public function update(Request $request, Workstation $workstation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('workstations')->ignore($workstation->id)],
            'type' => ['sometimes', Rule::enum(WorkstationType::class)],
            'location' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(WorkstationStatus::class)],
            'operator_ids' => 'nullable|array',
            'operator_ids.*' => 'exists:users,id'
        ]);

        $workstation->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? $workstation->type,
            'location' => $validated['location'],
            'status' => $validated['status'],
        ]);

        if (isset($validated['operator_ids'])) {
            $workstation->operators()->sync($validated['operator_ids']);
        }

        return response()->json($workstation->load('operators'));
    }

    /**
     * Usuwanie stanowiska.
     */
    public function destroy(Workstation $workstation)
    {
        // Sprawdź czy stanowisko nie ma aktywnego zadania
        if ($workstation->current_task_id) {
            return response()->json([
                'message' => 'Nie można usunąć stanowiska, które wykonuje zadanie.'
            ], 400);
        }

        $workstation->operators()->detach();
        $workstation->delete();

        return response()->json(['message' => 'Stanowisko usunięte pomyślnie']);
    }

    /**
     * Lista pracowników
     */
    public function workers()
    {
        // $workers = User::where('role', UserRole::PRODUCTION_EMPLOYEE)
        //     ->where('is_active', true)
        //     ->select('id', 'name', 'email')
        //     ->orderBy('name')
        //     ->get();

        $workers = User::where('is_active', true)
            ->select('id', 'name', 'email', 'role') // Dodałem 'role' żebyś widział kto to
            ->orderBy('name')
            ->get();

        return response()->json($workers);
    }

    public function attachService(Request $request, Workstation $workstation)
    {
        $request->validate(['assortment_id' => 'required|exists:assortment,id']);
        // Sprawdź czy to usługa
        $service = \App\Models\Assortment::findOrFail($request->assortment_id);
        if ($service->type !== \App\Enums\AssortmentType::SERVICE) {
            return response()->json(['message' => 'Można przypisać tylko usługi'], 400);
        }

        $workstation->allowedServices()->syncWithoutDetaching([$request->assortment_id]);
        return response()->json(['message' => 'Usługa przypisana']);
    }

    public function detachService(Workstation $workstation, $assortmentId)
    {
        $workstation->allowedServices()->detach($assortmentId);
        return response()->json(['message' => 'Usługa odpięta']);
    }

    public function getServices(Workstation $workstation)
    {
        return response()->json($workstation->allowedServices);
    }

    /**
     * NOWA METODA: Pobierz stanowiska przypisane do zalogowanego użytkownika.
     * Używane w panelu RCP.
     */
    public function forCurrentUser(Request $request)
    {
        $user = $request->user();

        // Jeśli to Admin/Manager, może widzieć wszystkie (opcjonalne, zależnie od logiki biznesowej)
        // Tutaj zakładamy, że na RCP widzi tylko swoje przypisane:

        $workstations = $user->workstations()
            ->with(['currentTask.productionOrder.variant']) // Załaduj aktywne zadania
            ->orderBy('name')
            ->get();

        return response()->json($workstations);
    }
}
