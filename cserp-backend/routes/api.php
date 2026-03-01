<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ProjectSeriesController;
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
use App\Http\Controllers\API\ProjectImageController;

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

    Route::get('users/for-select', [UserController::class, 'forSelect']);
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
    // PROJEKTY
    // =========================================================================

    // Pomocniczy — podgląd następnego numeru (frontend info, nie rezerwuje numeru)
    Route::get('projects/next-number', [ProjectController::class, 'nextNumber']);

    // Podsumowanie finansowe projektu (materiały + wyceny + koszty produkcji)
    Route::get('projects/{project}/financial-summary', [ProjectController::class, 'financialSummary']);

    // ── SERIE PROJEKTÓW ─────────────────────────────────────────────────────
    //
    // Uwaga: trasy z parametrami muszą być PRZED apiResource('projects'),
    // żeby Laravel nie traktował 'series' jako ID projektu.
    //
    // Pobierz wszystkie serie dla project_number projektu {project}
    Route::get('projects/{project}/series', [ProjectSeriesController::class, 'index']);

    // Pobierz warianty danej serii do selektora kopiowania (CreateSeriesDialog)
    Route::get('projects/{project}/series/variants', [ProjectSeriesController::class, 'variantsForSelector']);

    // Utwórz nową serię (pustą lub z kopiowaniem wybranych wariantów)
    Route::post('projects/{project}/series/create', [ProjectSeriesController::class, 'create']);

    // CRUD projektów
    Route::apiResource('projects', ProjectController::class);

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
    // =========================================================================

    // Pobierz wszystkie elementy (grupy + warianty) dla projektu — płaska lista
    Route::get('projects/{project}/variants', [VariantController::class, 'index']);

    // Utwórz nową GRUPĘ dla projektu (quantity=0 nadawane automatycznie przez backend)
    Route::post('projects/{project}/variants', [VariantController::class, 'store']);

    // Utwórz WARIANT jako dziecko istniejącej grupy lub wariantu
    Route::post('projects/{project}/variants/{parent}/children', [VariantController::class, 'storeChild']);

    // Szczegóły wariantu lub grupy
    Route::get('variants/{variant}', [VariantController::class, 'show']);

    // Aktualizacja grupy lub wariantu
    Route::put('variants/{variant}', [VariantController::class, 'update']);

    // Usuń wariant lub grupę
    Route::delete('variants/{variant}', [VariantController::class, 'destroy']);

    // Zmień status wariantu
    Route::patch('variants/{variant}/status', [VariantController::class, 'updateStatus']);

    // Recenzja prototypu
    Route::post('variants/{variant}/review', [VariantController::class, 'reviewPrototype']);

    // Duplikuj wariant lub grupę
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
    // ZDJĘCIA PROJEKTU
    // =========================================================================

    Route::get('projects/{project}/images', [ProjectImageController::class, 'index']);
    Route::post('projects/{project}/images', [ProjectImageController::class, 'store']);
    Route::delete('project-images/{image}', [ProjectImageController::class, 'destroy']);

    // =========================================================================
    // RCP — Panel pracownika produkcyjnego
    // =========================================================================

    Route::get('rcp/active-task', [RcpController::class, 'checkActiveTask']);
    Route::get('rcp/tasks/{task}', [RcpController::class, 'getTaskDetails']);
    Route::get('rcp/variants', [RcpController::class, 'getAvailableVariants']);
    Route::post('rcp/start', [RcpController::class, 'start']);
    Route::post('rcp/stop/{task}', [RcpController::class, 'stop']);
    Route::post('rcp/pause/{task}', [RcpController::class, 'pause']);
    Route::post('rcp/resume/{task}', [RcpController::class, 'resume']);

    // =========================================================================
    // RCP ADMIN
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

    Route::get('workstations-my-list', [WorkstationController::class, 'forCurrentUser']);
    Route::get('workers-list', [WorkstationController::class, 'workers']);
    Route::apiResource('workstations', WorkstationController::class);
    Route::post('workstations/{workstation}/operators', [WorkstationController::class, 'addOperator']);
    Route::delete('workstations/{workstation}/operators/{user}', [WorkstationController::class, 'removeOperator']);

    Route::get('workstations/{workstation}/services', [WorkstationController::class, 'getServices']);
    Route::post('workstations/{workstation}/services', [WorkstationController::class, 'attachService']);
    Route::delete('workstations/{workstation}/services/{assortment}', [WorkstationController::class, 'detachService']);

    // =========================================================================
    // INNE
    // =========================================================================

    Route::get('/metadata', [MetadataController::class, 'index']);
    Route::get('nip/{nip}', [NipController::class, 'lookup']);
});
