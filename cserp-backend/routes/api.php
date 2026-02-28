<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\OrderSeriesController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\AssortmentController;
use App\Http\Controllers\API\QuotationController;
use App\Http\Controllers\API\VariantController;
use App\Http\Controllers\API\PrototypeController;
use App\Http\Controllers\API\MetadataController;
use App\Http\Controllers\API\NipController;
use App\Http\Controllers\API\WorkstationController;
use App\Http\Controllers\API\RcpController;
use App\Http\Controllers\API\RcpAdminController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VariantMaterialController;
use App\Http\Controllers\API\PrototypeMaterialController;
use App\Http\Controllers\API\OrderImageController;
use App\Http\Controllers\API\ProductionController;
use App\Http\Controllers\API\DeliveryController;

// =========================================================================
// PUBLICZNE — bez autoryzacji
// =========================================================================

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/login-pin', [AuthController::class, 'loginPin']);

// =========================================================================
// CHRONIONE — wymagają auth:sanctum
// =========================================================================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'me']);

    // =========================================================================
    // UŻYTKOWNICY
    // =========================================================================

    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::apiResource('users', UserController::class);

    // =========================================================================
    // KLIENCI
    // =========================================================================

    Route::get('customers/for-select', [CustomerController::class, 'forSelect']);
    Route::get('customers/{customer}/statistics', [CustomerController::class, 'statistics']);
    Route::patch('customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive']);
    Route::apiResource('customers', CustomerController::class);

    // =========================================================================
    // ZAMÓWIENIA
    // =========================================================================

    // Pomocniczy — podgląd następnego numeru (frontend info, nie rezerwuje numeru)
    Route::get('orders/next-number', [OrderController::class, 'nextNumber']);

    // Podsumowanie finansowe zamówienia (materiały + wyceny + koszty produkcji)
    Route::get('orders/{order}/financial-summary', [OrderController::class, 'financialSummary']);

    // ── SERIE ZAMÓWIEŃ ─────────────────────────────────────────────────────
    //
    // Uwaga: trasy z parametrami muszą być PRZED apiResource('orders'),
    // żeby Laravel nie traktował 'series' jako ID zamówienia.
    //
    // Pobierz wszystkie serie dla order_number zamówienia {order}
    Route::get('orders/{order}/series', [OrderSeriesController::class, 'index']);

    // Pobierz warianty danej serii do selektora kopiowania (CreateSeriesDialog)
    Route::get('orders/{order}/series/variants', [OrderSeriesController::class, 'variantsForSelector']);

    // Utwórz nową serię (pustą lub z kopiowaniem wybranych wariantów)
    Route::post('orders/{order}/series/create', [OrderSeriesController::class, 'create']);

    // CRUD zamówień
    Route::apiResource('orders', OrderController::class);

    // =========================================================================
    // ASORTYMENT (materiały + usługi)
    // =========================================================================

    Route::post('assortment/batch-check', [AssortmentController::class, 'batchCheckOrCreate']);
    Route::get('assortment/{assortment}/history', [AssortmentController::class, 'history']);
    Route::get('assortment-materials', [AssortmentController::class, 'materials']);
    Route::get('assortment-services', [AssortmentController::class, 'services']);
    Route::get('assortment-categories', [AssortmentController::class, 'categories']);
    Route::patch('assortment/{assortment}/toggle-active', [AssortmentController::class, 'toggleActive']);
    Route::apiResource('assortment', AssortmentController::class);

    // =========================================================================
    // WARIANTY PRODUKTOWE
    //
    // ARCHITEKTURA HIERARCHII:
    //
    //   Discriminator: quantity
    //     quantity = 0  → GRUPA   (kontener, nie jest produkowana)
    //     quantity ≥ 1  → WARIANT (ma lifecycle produkcji)
    //
    //   Drzewo przykładowe:
    //     A (quantity=0, GRUPA)
    //       A1 (quantity=10, WARIANT)
    //       A2 (quantity=5,  WARIANT)
    //         A2_1 (quantity=3, WARIANT podrzędny)
    //     B (quantity=0, GRUPA)
    //       B1 (quantity=8, WARIANT)
    //
    // =========================================================================

    // Pobierz wszystkie elementy (grupy + warianty) dla zamówienia — płaska lista
    Route::get('orders/{order}/variants', [VariantController::class, 'index']);

    // Utwórz nową GRUPĘ dla zamówienia (quantity=0 nadawane automatycznie przez backend)
    // Backend nadaje kolejną literę: A, B, C...
    // Payload: { name, description? }
    Route::post('orders/{order}/variants', [VariantController::class, 'store']);

    // Utwórz WARIANT jako dziecko istniejącej grupy lub wariantu
    // Backend nadaje numer wg reguły: A → A1, A2; A1 → A1_1, A1_2
    // Payload: { name, quantity (≥1), type (SERIAL|PROTOTYPE), description? }
    Route::post('orders/{order}/variants/{parent}/children', [VariantController::class, 'storeChild']);

    // Szczegóły wariantu lub grupy
    Route::get('variants/{variant}', [VariantController::class, 'show']);

    // Aktualizacja grupy lub wariantu
    // UWAGA: backend blokuje konwersję quantity=0 ↔ quantity≥1 (nie można zmieniać typu)
    Route::put('variants/{variant}', [VariantController::class, 'update']);

    // Usuń wariant lub grupę
    // ?force=true — wymagane dla grup z dziećmi; kasuje rekurencyjnie całe drzewo
    // Bez force=true backend zwraca 422 jeśli element ma dzieci
    Route::delete('variants/{variant}', [VariantController::class, 'destroy']);

    // Zmień status wariantu (tylko dla wariantów — grup nie dotyczą statusy)
    // Payload: { status: PENDING|IN_PROGRESS|COMPLETED|CANCELLED|... }
    Route::patch('variants/{variant}/status', [VariantController::class, 'updateStatus']);

    // Recenzja prototypu — zatwierdź lub odrzuć
    // Payload: { action: approve|reject, feedback_notes?: string }
    Route::post('variants/{variant}/review', [VariantController::class, 'reviewPrototype']);

    // Duplikuj wariant lub grupę
    // Dla wariantu: { relation: sibling|child, name, quantity, type, copy_quotation, copy_materials, description? }
    // Dla grupy:    { relation: sibling, name, copy_children, description? }
    Route::post('variants/{variant}/duplicate', [VariantController::class, 'duplicate']);

    // =========================================================================
    // MATERIAŁY WARIANTU (produkcja seryjna)
    // =========================================================================

    Route::get('variants/{variant}/materials', [VariantMaterialController::class, 'index']);
    Route::post('variants/{variant}/materials', [VariantMaterialController::class, 'store']);
    Route::post('variants/{variant}/materials/batch', [VariantMaterialController::class, 'batchStore']);
    Route::post('variants/{variant}/materials/mark-all-ordered', [VariantMaterialController::class, 'markAllOrdered']);
    Route::get('variant-materials/{material}', [VariantMaterialController::class, 'show']);
    Route::put('variant-materials/{material}', [VariantMaterialController::class, 'update']);
    Route::delete('variant-materials/{material}', [VariantMaterialController::class, 'destroy']);
    Route::patch('variant-materials/{material}/status', [VariantMaterialController::class, 'updateStatus']);

    // =========================================================================
    // WYCENY
    // =========================================================================

    // UWAGA: Wyceny tworzone są per WARIANT (nie per grupę).
    // Grupy nie mają wycen — wyceny dotyczą konkretnych wariantów produkcyjnych.
    Route::get('variants/{variant}/quotations', [QuotationController::class, 'index']);
    Route::post('variants/{variant}/quotations', [QuotationController::class, 'store']);
    Route::get('quotations/{quotation}', [QuotationController::class, 'show']);
    Route::put('quotations/{quotation}', [QuotationController::class, 'update']);
    Route::patch('quotations/{quotation}/approve', [QuotationController::class, 'approve']);
    Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf']);
    Route::post('quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate']);
    Route::post('quotations/{quotation}/export-materials', [QuotationController::class, 'exportMaterials']);

    // =========================================================================
    // PROTOTYPY
    // =========================================================================

    Route::get('variants/{variant}/prototypes', [PrototypeController::class, 'index']);
    Route::post('variants/{variant}/prototypes', [PrototypeController::class, 'store']);
    Route::get('prototypes/{prototype}', [PrototypeController::class, 'show']);
    Route::put('prototypes/{prototype}', [PrototypeController::class, 'update']);
    Route::patch('prototypes/{prototype}/approve', [PrototypeController::class, 'approve']);
    Route::patch('prototypes/{prototype}/reject', [PrototypeController::class, 'reject']);

    // =========================================================================
    // MATERIAŁY PROTOTYPU
    // =========================================================================

    Route::get('prototypes/{prototype}/materials', [PrototypeMaterialController::class, 'index']);
    Route::post('prototypes/{prototype}/materials', [PrototypeMaterialController::class, 'store']);
    Route::post('prototypes/{prototype}/materials/batch', [PrototypeMaterialController::class, 'batchStore']);
    Route::get('prototype-materials/{material}', [PrototypeMaterialController::class, 'show']);
    Route::put('prototype-materials/{material}', [PrototypeMaterialController::class, 'update']);
    Route::delete('prototype-materials/{material}', [PrototypeMaterialController::class, 'destroy']);
    Route::patch('prototype-materials/{material}/status', [PrototypeMaterialController::class, 'updateStatus']);

    // =========================================================================
    // ZDJĘCIA ZAMÓWIENIA
    // =========================================================================

    Route::get('orders/{order}/images', [OrderImageController::class, 'index']);
    Route::post('orders/{order}/images', [OrderImageController::class, 'store']);
    Route::delete('order-images/{image}', [OrderImageController::class, 'destroy']);

    // =========================================================================
    // PRODUKCJA
    // =========================================================================

    Route::get('variants/{variant}/production', [ProductionController::class, 'show']);
    Route::post('variants/{variant}/production', [ProductionController::class, 'store']);
    Route::get('production/{production}', [ProductionController::class, 'details']);
    Route::put('production/{production}', [ProductionController::class, 'update']);
    Route::get('production/{production}/services', [ProductionController::class, 'services']);
    Route::post('production/{production}/services', [ProductionController::class, 'addService']);
    Route::put('production-services/{service}', [ProductionController::class, 'updateService']);
    Route::delete('production-services/{service}', [ProductionController::class, 'deleteService']);

    // =========================================================================
    // DOSTAWY
    // =========================================================================

    Route::get('variants/{variant}/deliveries', [DeliveryController::class, 'index']);
    Route::post('variants/{variant}/deliveries', [DeliveryController::class, 'store']);
    Route::get('deliveries/{delivery}', [DeliveryController::class, 'show']);
    Route::put('deliveries/{delivery}', [DeliveryController::class, 'update']);
    Route::patch('deliveries/{delivery}/complete', [DeliveryController::class, 'complete']);
    Route::delete('deliveries/{delivery}', [DeliveryController::class, 'destroy']);

    // =========================================================================
    // RCP — Panel pracownika produkcyjnego
    //
    // Pracownik loguje się przez PIN, widzi swoje zadania, obsługuje timer.
    // =========================================================================

    // Sprawdź czy pracownik ma aktywne zadanie (po powrocie do aplikacji)
    Route::get('rcp/active-task', [RcpController::class, 'checkActiveTask']);

    // Szczegóły konkretnego zadania (z timerem)
    Route::get('rcp/tasks/{task}', [RcpController::class, 'getTaskDetails']);

    // Lista wariantów gotowych do obsługi przez pracownika
    Route::get('rcp/variants', [RcpController::class, 'getAvailableVariants']);

    // Cykl życia timera
    Route::post('rcp/start', [RcpController::class, 'start']);
    Route::post('rcp/stop/{task}', [RcpController::class, 'stop']);
    Route::post('rcp/pause/{task}', [RcpController::class, 'pause']);
    Route::post('rcp/resume/{task}', [RcpController::class, 'resume']);

    // =========================================================================
    // RCP ADMIN — zarządzanie zadaniami i ręczna korekta logów czasu
    // =========================================================================

    Route::get('admin/rcp/tasks', [RcpAdminController::class, 'index']);
    Route::put('admin/rcp/tasks/{task}', [RcpAdminController::class, 'update']);
    Route::get('admin/rcp/tasks/{task}/logs', [RcpAdminController::class, 'getLogs']);
    Route::post('admin/rcp/tasks/{task}/logs', [RcpAdminController::class, 'storeLog']);
    Route::put('admin/rcp/logs/{log}', [RcpAdminController::class, 'updateLog']);
    Route::delete('admin/rcp/logs/{log}', [RcpAdminController::class, 'destroyLog']);

    // =========================================================================
    // STANOWISKA PRACY
    // =========================================================================

    Route::apiResource('workstations', WorkstationController::class);
    Route::post('workstations/{workstation}/operators', [WorkstationController::class, 'addOperator']);
    Route::delete('workstations/{workstation}/operators/{user}', [WorkstationController::class, 'removeOperator']);

    // Usługi przypisane do stanowiska (co stanowisko może wykonywać)
    Route::get('workstations/{workstation}/services', [WorkstationController::class, 'services']);
    Route::post('workstations/{workstation}/services/{assortment}', [WorkstationController::class, 'attachService']);
    Route::delete('workstations/{workstation}/services/{assortment}', [WorkstationController::class, 'detachService']);

    // =========================================================================
    // INNE
    // =========================================================================

    // Metadata — statusy, typy, enumy (ładowane raz przy starcie aplikacji)
    Route::get('/metadata', [MetadataController::class, 'index']);

    // Lookup GUS po NIP (autocomplete przy tworzeniu klienta)
    Route::get('nip/{nip}', [NipController::class, 'lookup']);
});
