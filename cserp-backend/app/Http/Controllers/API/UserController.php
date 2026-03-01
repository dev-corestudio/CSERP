<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Traits\Paginatable;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException; // IMPORT WYJĄTKU

class UserController extends Controller
{
    use Paginatable;

    /**
     * Lista użytkowników z paginacją server-side.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Wyszukiwanie
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtracja po roli
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filtracja po statusie
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Sortowanie z whitelistą
        $this->applySorting($query, $request, [
            'name',
            'email',
            'role',
            'created_at',
        ], 'name', 'asc');

        // Paginacja
        $users = $this->paginateQuery($query, $request);

        return UserResource::collection($users);
    }

    /**
     * Lista użytkowników mogących być opiekunami projektu
     * (Handlowiec + Project Manager), tylko aktywni.
     *
     * GET /api/users/for-select
     */
    public function forSelect()
    {
        $users = User::whereIn('role', [UserRole::TRADER, UserRole::PROJECT_MANAGER])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return response()->json($users);
    }

    /**
     * Tworzenie użytkownika
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::enum(UserRole::class)],
            'pin_code' => 'nullable|string|size:4',
            'is_active' => 'boolean',
        ]);

        // WALIDACJA UNIKALNOŚCI PIN (HASH)
        if (!empty($validated['pin_code'])) {
            $this->ensurePinIsUnique($validated['pin_code']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'pin_code' => isset($validated['pin_code']) ? Hash::make($validated['pin_code']) : null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return new UserResource($user);
    }

    /**
     * Szczegóły użytkownika
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Aktualizacja użytkownika
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'pin_code' => 'nullable|string|size:4',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'role' => $validated['role'] ?? $user->role,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ];

        // Aktualizuj hasło tylko jeśli podano
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Aktualizuj PIN tylko jeśli podano (i nie jest pusty)
        if (array_key_exists('pin_code', $validated)) {
            $newPin = $validated['pin_code'];

            if ($newPin) {
                // WALIDACJA UNIKALNOŚCI PIN (HASH) - z wykluczeniem obecnego użytkownika
                $this->ensurePinIsUnique($newPin, $user->id);
                $data['pin_code'] = Hash::make($newPin);
            } else {
                // Jeśli pusty string/null -> usuwamy PIN
                $data['pin_code'] = null;
            }
        }

        $user->update($data);

        return new UserResource($user);
    }

    /**
     * Helper: Sprawdź czy PIN jest unikalny w bazie (porównując hashe)
     */
    private function ensurePinIsUnique(string $plainPin, ?int $ignoreUserId = null): void
    {
        // Pobierz wszystkich użytkowników, którzy mają PIN, z wyłączeniem edytowanego
        $query = User::whereNotNull('pin_code');

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        // Pobieramy tylko niezbędne pola dla wydajności (chunkowanie przy dużej bazie)
// Przy małej/średniej firmie (do kilkuset pracowników) full scan jest OK.
        $usersWithPin = $query->get(['id', 'pin_code']);

        foreach ($usersWithPin as $otherUser) {
            if (Hash::check($plainPin, $otherUser->pin_code)) {
                throw ValidationException::withMessages([
                    'pin_code' => ['Ten kod PIN jest już przypisany do innego pracownika.'],
                ]);
            }
        }
    }

    /**
     * Usuwanie użytkownika
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Nie możesz usunąć własnego konta.'], 403);
        }

        $hasHistory = DB::table('production_services')->where('assigned_to_user_id', $user->id)->exists()
            || DB::table('quotations')->where('approved_by_user_id', $user->id)->exists();

        if ($hasHistory) {
            return response()->json([
                'message' => 'Nie można usunąć użytkownika, który posiada historię operacji. Zamiast tego dezaktywuj konto.'
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Użytkownik usunięty pomyślnie']);
    }

    /**
     * Zmiana statusu aktywności
     */
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Nie możesz zmienić statusu własnego konta.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Użytkownik aktywowany' : 'Użytkownik dezaktywowany',
            'user' => new UserResource($user)
        ]);
    }
}
