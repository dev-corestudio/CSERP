<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Resources\UserResource; // Import Resource
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Standardowe logowanie (Email + Hasło) -> Rola z bazy (np. ADMIN)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Podane dane są nieprawidłowe.'],
            ]);
        }

        // Zwykły token bez specjalnych uprawnień
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user), // Używamy Resource dla spójności
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Wylogowano pomyślnie'
        ]);
    }

    // Pobieranie usera (np. przy odświeżaniu F5)
    public function me(Request $request)
    {
        // Resource sam sprawdzi, czy token ma flagę 'act-as-worker' i podmieni rolę
        return new UserResource($request->user());
    }

    // Logowanie PIN -> Wymuszona rola PRODUCTION_EMPLOYEE
    public function loginPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $inputPin = $request->input('pin');

        $usersWithPin = User::where('is_active', true)
            ->whereNotNull('pin_code')
            ->get();

        $matchedUser = null;

        foreach ($usersWithPin as $user) {
            if (Hash::check($inputPin, $user->pin_code)) {
                $matchedUser = $user;
                break;
            }
        }

        if (!$matchedUser) {
            throw ValidationException::withMessages([
                'pin' => ['Nieprawidłowy kod PIN.'],
            ]);
        }

        // Token z flagą "act-as-worker"
        $token = $matchedUser->createToken('auth_token_pin', ['act-as-worker'])->plainTextToken;

        $matchedUser->role = UserRole::PRODUCTION_EMPLOYEE;

        return response()->json([
            'token' => $token,
            // UserResource sprawdzi flagę tokena (lub Requesta w tym kontekście) i podmieni rolę
            'user' => new UserResource($matchedUser),
        ]);
    }
}
