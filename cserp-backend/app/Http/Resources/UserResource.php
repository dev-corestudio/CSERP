<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\UserRole;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isActingAsWorker = false;

        // Scenariusz 1: Logowanie PIN-em (w tym momencie token jest dopiero tworzony,
        // nie ma go w nagłówku requestu, więc sprawdzamy endpoint)
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();

            // ▼▼▼ POPRAWKA ▼▼▼
            // Sprawdzamy surowe uprawnienia (abilities).
            // Jeśli token ma '*', to jest to standardowe logowanie -> nie podmieniamy roli.
            // Podmieniamy TYLKO wtedy, gdy NIE ma '*' ORAZ ma 'act-as-worker'.
            if (!in_array('*', $token->abilities)) {
                $isActingAsWorker = $user->tokenCan('act-as-worker');
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Jeśli działa jako worker -> pokaż PRODUCTION_EMPLOYEE, w przeciwnym razie prawdziwa rola
            'role' => $isActingAsWorker ? UserRole::PRODUCTION_EMPLOYEE : $this->role,
            'is_active' => $this->is_active,
            'has_pin' => !is_null($this->pin_code),
        ];
    }
}
