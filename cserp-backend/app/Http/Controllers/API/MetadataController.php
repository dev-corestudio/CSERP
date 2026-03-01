<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Enums\VariantType;
use App\Enums\VariantStatus;
use App\Enums\{
    UserRole,
    CustomerType,
    AssortmentType,
    AssortmentUnit,
    ProjectOverallStatus,
    PaymentStatus,
    WorkstationType,
    WorkstationStatus,
    ProductionStatus,
    ProjectPriority,
    TestResult,
    DeliveryStatus,
    InvoiceStatus,
    PaymentMethod,
    EventType,
    AssortmentHistoryAction,
    MaterialStatus,
};
use App\Models\Assortment;

class MetadataController extends Controller
{
    public function index()
    {
        $map = fn($enumClass) => collect($enumClass::cases())->map(fn($e) => [
            'value' => $e->value,
            'label' => $e->label(),
        ]);

        $mapWithColor = fn($enumClass) => collect($enumClass::cases())->map(fn($e) => [
            'value' => $e->value,
            'label' => $e->label(),
            'color' => method_exists($e, 'color') ? $e->color() : null,
        ]);

        $mapFull = fn($enumClass) => collect($enumClass::cases())->map(fn($e) => [
            'value' => $e->value,
            'label' => $e->label(),
            'color' => method_exists($e, 'color') ? $e->color() : null,
            'icon' => method_exists($e, 'icon') ? $e->icon() : null,
        ]);

        return response()->json([
            // Metadata dla wariantów
            'variant_types' => $mapWithColor(VariantType::class),
            'variant_statuses' => $mapFull(VariantStatus::class),

            'user_roles' => $map(UserRole::class),
            'customer_types' => $map(CustomerType::class),
            'assortment_types' => $map(AssortmentType::class),
            'units' => $map(AssortmentUnit::class),
            'project_statuses' => $mapFull(ProjectOverallStatus::class),
            'payment_statuses' => $mapFull(PaymentStatus::class),
            'project_priorities' => $mapWithColor(ProjectPriority::class),
            'workstation_types' => $map(WorkstationType::class),
            'workstation_statuses' => $mapWithColor(WorkstationStatus::class),
            'production_statuses' => $map(ProductionStatus::class),
            'test_results' => $mapWithColor(TestResult::class),
            'event_types' => $map(EventType::class),
            'material_statuses' => $mapFull(MaterialStatus::class),
            'delivery_statuses' => $mapWithColor(DeliveryStatus::class),
            'invoice_statuses' => $mapWithColor(InvoiceStatus::class),
            'payment_methods' => $map(PaymentMethod::class),
            'assortment_history_actions' => $map(AssortmentHistoryAction::class),
            'assortment_categories' => Assortment::distinct()
                ->whereNotNull('category')
                ->pluck('category')
                ->sort()
                ->values(),
        ]);
    }
}
