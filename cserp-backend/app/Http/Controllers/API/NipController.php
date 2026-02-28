<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * NipController - Pobieranie danych firmy po NIP
 *
 * Używa API Białej Listy VAT Ministerstwa Finansów
 * https://www.podatki.gov.pl/wykaz-podatnikow-vat-wyszukiwarka
 */
class NipController extends Controller
{
    /**
     * Pobierz dane firmy po numerze NIP
     *
     * @param string $nip - numer NIP (10 cyfr)
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookup(string $nip)
    {
        // Walidacja NIP - usuń myślniki i spacje
        $nip = preg_replace('/[^0-9]/', '', $nip);

        if (strlen($nip) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'NIP musi mieć 10 cyfr'
            ], 422);
        }

        // Walidacja sumy kontrolnej NIP
        if (!$this->validateNip($nip)) {
            return response()->json([
                'success' => false,
                'message' => 'Nieprawidłowy numer NIP'
            ], 422);
        }

        // Cache na 24h - żeby nie bombardować API
        $cacheKey = 'nip_' . $nip;

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        try {
            // API Białej Listy VAT - Ministerstwo Finansów
            $date = date('Y-m-d');
            $url = "https://wl-api.mf.gov.pl/api/search/nip/{$nip}?date={$date}";

            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['result']['subject']) && $data['result']['subject'] !== null) {
                    $subject = $data['result']['subject'];

                    $result = [
                        'success' => true,
                        'data' => [
                            'name' => $subject['name'] ?? '',
                            'nip' => $subject['nip'] ?? $nip,
                            'regon' => $subject['regon'] ?? '',
                            'address' => $this->formatAddress($subject),
                            'working_address' => $subject['workingAddress'] ?? '',
                            'krs' => $subject['krs'] ?? '',
                            'status_vat' => $subject['statusVat'] ?? '',
                            'account_numbers' => $subject['accountNumbers'] ?? [],
                        ]
                    ];

                    // Cache na 24h
                    Cache::put($cacheKey, $result, now()->addHours(24));

                    return response()->json($result);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nie znaleziono firmy o podanym NIP'
                    ], 404);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Błąd połączenia z API Ministerstwa Finansów'
            ], 503);

        } catch (\Exception $e) {
            \Log::error('NIP lookup error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas sprawdzania NIP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatuj adres z danych API
     */
    private function formatAddress(array $subject): string
    {
        // API zwraca różne warianty adresu
        if (!empty($subject['workingAddress'])) {
            return $subject['workingAddress'];
        }

        if (!empty($subject['residenceAddress'])) {
            return $subject['residenceAddress'];
        }

        return '';
    }

    /**
     * Walidacja sumy kontrolnej NIP
     */
    private function validateNip(string $nip): bool
    {
        if (strlen($nip) !== 10) {
            return false;
        }

        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += $weights[$i] * (int) $nip[$i];
        }

        $checksum = $sum % 11;

        // Checksum nie może być 10
        if ($checksum === 10) {
            return false;
        }

        return $checksum === (int) $nip[9];
    }
}
