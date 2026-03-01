<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Variant;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemMaterial;
use App\Models\QuotationItemService;
use App\Models\VariantMaterial;
use App\Enums\MaterialStatus;
use App\Enums\VariantStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Enums\PaymentStatus;
use App\Enums\ProjectOverallStatus;
use App\Enums\ProjectPriority;


/**
 * SeriesService — logika tworzenia nowych serii projektów
 *
 * Seria = to samo zamówienie (ten sam project_number) produkowane kolejny raz.
 * Przykład: P/0001/0001 → P/0001/0002 → P/0001/0003
 */
class SeriesService
{
    // =========================================================================
    // TWORZENIE NOWEJ SERII
    // =========================================================================

    /**
     * Utwórz nową serię dla danego projektu (tego samego project_number).
     */
    public function createNewSeries(
        Project $sourceProject,
        array $projectData,
        ?array $variantsToCopy = null
    ): Project {
        return DB::transaction(function () use ($sourceProject, $projectData, $variantsToCopy) {

            $newSeries = Project::generateSeries($sourceProject->project_number);

            $newProject = Project::create([
                'customer_id' => $sourceProject->customer_id,
                'project_number' => $sourceProject->project_number,
                'series' => $newSeries,
                'description' => $projectData['description'] ?? $sourceProject->description,
                'planned_delivery_date' => $projectData['planned_delivery_date'] ?? null,
                'priority' => isset($projectData['priority'])
                    ? ProjectPriority::from(strtoupper($projectData['priority']))
                    : ProjectPriority::NORMAL,
                'overall_status' => ProjectOverallStatus::DRAFT,
                'payment_status' => PaymentStatus::UNPAID,
            ]);

            Log::info(
                "SeriesService: Utworzono nową serię #{$newProject->id} " .
                "({$newProject->full_project_number}) na podstawie #{$sourceProject->id} " .
                "({$sourceProject->full_project_number})"
            );

            if (!empty($variantsToCopy)) {
                foreach ($variantsToCopy as $variantConfig) {
                    $this->copyVariantToProject(
                        sourceVariantId: $variantConfig['source_variant_id'],
                        targetProject: $newProject,
                        copyQuotation: (bool) ($variantConfig['copy_quotation'] ?? false),
                        copyMaterials: (bool) ($variantConfig['copy_materials'] ?? false)
                    );
                }
            }

            $newProject->load(['customer', 'variants']);

            return $newProject;
        });
    }

    // =========================================================================
    // KOPIOWANIE WARIANTU
    // =========================================================================

    public function copyVariantToProject(
        int $sourceVariantId,
        Project $targetProject,
        bool $copyQuotation = false,
        bool $copyMaterials = false
    ): Variant {
        $sourceVariant = Variant::with([
            'quotations.items.materials',
            'quotations.items.services',
            'materials',
        ])->findOrFail($sourceVariantId);

        $sourceProject = $sourceVariant->project;
        if ($sourceProject->project_number !== $targetProject->project_number) {
            throw new \InvalidArgumentException(
                "Wariant #{$sourceVariantId} należy do projektu " .
                "{$sourceProject->full_project_number}, " .
                "które ma inny numer niż docelowe {$targetProject->full_project_number}."
            );
        }

        $nextVariantNumber = $this->getNextVariantNumber($targetProject);

        $newVariant = Variant::create([
            'project_id' => $targetProject->id,
            'parent_variant_id' => null,
            'variant_number' => $nextVariantNumber,
            'name' => $sourceVariant->name,
            'description' => $sourceVariant->description,
            'quantity' => $sourceVariant->quantity,
            'type' => $sourceVariant->type,
            'status' => VariantStatus::QUOTATION,
            'is_approved' => false,
            'feedback_notes' => null,
            'approved_prototype_id' => null,
        ]);

        Log::info(
            "SeriesService: Skopiowano wariant #{$sourceVariant->id} " .
            "({$sourceVariant->name}) → #{$newVariant->id} " .
            "w projekcie #{$targetProject->id} ({$targetProject->full_project_number})"
        );

        if ($copyQuotation) {
            $this->copyBestQuotation($sourceVariant, $newVariant);
        }

        if ($copyMaterials) {
            $this->copyVariantMaterials($sourceVariant, $newVariant);
        }

        return $newVariant->fresh(['quotations', 'materials']);
    }

    // =========================================================================
    // KOPIOWANIE WYCENY
    // =========================================================================

    private function copyBestQuotation(Variant $sourceVariant, Variant $targetVariant): ?Quotation
    {
        $sourceQuotation = $sourceVariant->quotations()
            ->with(['items.materials', 'items.services'])
            ->where('is_approved', true)
            ->first();

        if (!$sourceQuotation) {
            $sourceQuotation = $sourceVariant->quotations()
                ->with(['items.materials', 'items.services'])
                ->orderByDesc('version_number')
                ->first();
        }

        if (!$sourceQuotation) {
            Log::warning(
                "SeriesService: Wariant #{$sourceVariant->id} nie ma żadnej wyceny do skopiowania."
            );
            return null;
        }

        $newQuotation = Quotation::create([
            'variant_id' => $targetVariant->id,
            'version_number' => 1,
            'total_materials_cost' => $sourceQuotation->total_materials_cost,
            'total_services_cost' => $sourceQuotation->total_services_cost,
            'total_net' => $sourceQuotation->total_net,
            'total_gross' => $sourceQuotation->total_gross,
            'margin_percent' => $sourceQuotation->margin_percent,
            'is_approved' => false,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'notes' => $this->buildCopyNote($sourceQuotation, $sourceVariant),
        ]);

        foreach ($sourceQuotation->items as $sourceItem) {
            $newItem = QuotationItem::create([
                'quotation_id' => $newQuotation->id,
                'materials_cost' => $sourceItem->materials_cost,
                'services_cost' => $sourceItem->services_cost,
                'subtotal' => $sourceItem->subtotal,
            ]);

            foreach ($sourceItem->materials as $material) {
                QuotationItemMaterial::create([
                    'quotation_item_id' => $newItem->id,
                    'assortment_item_id' => $material->assortment_item_id,
                    'quantity' => $material->quantity,
                    'unit' => $material->unit,
                    'unit_price' => $material->unit_price,
                    'total_cost' => $material->total_cost,
                    'notes' => $material->notes,
                ]);
            }

            foreach ($sourceItem->services as $service) {
                QuotationItemService::create([
                    'quotation_item_id' => $newItem->id,
                    'assortment_item_id' => $service->assortment_item_id,
                    'estimated_quantity' => $service->estimated_quantity,
                    'estimated_time_hours' => $service->estimated_time_hours,
                    'unit' => $service->unit,
                    'unit_price' => $service->unit_price,
                    'total_cost' => $service->total_cost,
                    'notes' => $service->notes,
                ]);
            }
        }

        Log::info(
            "SeriesService: Skopiowano wycenę #{$sourceQuotation->id} " .
            "(v{$sourceQuotation->version_number}) → #{$newQuotation->id} (v1) " .
            "dla wariantu #{$targetVariant->id}"
        );

        return $newQuotation;
    }

    // =========================================================================
    // KOPIOWANIE MATERIAŁÓW
    // =========================================================================

    private function copyVariantMaterials(Variant $sourceVariant, Variant $targetVariant): int
    {
        $sourceMaterials = $sourceVariant->materials;

        if ($sourceMaterials->isEmpty()) {
            Log::warning(
                "SeriesService: Wariant #{$sourceVariant->id} nie ma materiałów do skopiowania."
            );
            return 0;
        }

        $count = 0;

        foreach ($sourceMaterials as $sourceMaterial) {
            VariantMaterial::create([
                'variant_id' => $targetVariant->id,
                'assortment_id' => $sourceMaterial->assortment_id,
                'quantity' => $sourceMaterial->quantity,
                'unit' => $sourceMaterial->unit,
                'unit_price' => $sourceMaterial->unit_price,
                'total_cost' => $sourceMaterial->total_cost,
                'status' => MaterialStatus::NOT_ORDERED,
                'notes' => $sourceMaterial->notes,
                'expected_delivery_date' => null,
                'ordered_at' => null,
                'received_at' => null,
                'quantity_in_stock' => 0,
                'quantity_ordered' => 0,
                'supplier' => $sourceMaterial->supplier,
            ]);
            $count++;
        }

        Log::info(
            "SeriesService: Skopiowano {$count} materiałów " .
            "z wariantu #{$sourceVariant->id} → #{$targetVariant->id}"
        );

        return $count;
    }

    // =========================================================================
    // POMOCNICZE
    // =========================================================================

    private function getNextVariantNumber(Project $project): string
    {
        $existingLetters = Variant::where('project_id', $project->id)
            ->whereNull('parent_variant_id')
            ->whereRaw('LENGTH(variant_number) = 1')
            ->pluck('variant_number')
            ->toArray();

        $letter = 'A';
        while (in_array($letter, $existingLetters)) {
            $letter = chr(ord($letter) + 1);
        }

        return $letter;
    }

    private function buildCopyNote(Quotation $sourceQuotation, Variant $sourceVariant): string
    {
        $sourceProject = $sourceVariant->project;
        return "[Kopia z {$sourceProject->full_project_number}, " .
            "wariant #{$sourceVariant->variant_number} ({$sourceVariant->name}), " .
            "wycena v{$sourceQuotation->version_number}" .
            ($sourceQuotation->is_approved ? ', zatwierdzona' : '') .
            "]";
    }

    // =========================================================================
    // POBIERANIE SERII
    // =========================================================================

    public function getAllSeriesForProjectNumber(string $projectNumber)
    {
        return Project::with(['customer', 'variants'])
            ->where('project_number', $projectNumber)
            ->orderBy('series', 'asc')
            ->get();
    }

    public function getVariantsForCopySelector(Project $project)
    {
        return Variant::with(['approvedQuotation', 'materials'])
            ->where('project_id', $project->id)
            ->orderBy('variant_number')
            ->get()
            ->map(function (Variant $variant) {
                return [
                    'id' => $variant->id,
                    'variant_number' => $variant->variant_number,
                    'name' => $variant->name,
                    'quantity' => $variant->quantity,
                    'status' => $variant->status,
                    'type' => $variant->type,
                    'has_quotation' => $variant->quotations()->exists(),
                    'has_approved_quotation' => $variant->approvedQuotation !== null,
                    'has_materials' => $variant->materials->isNotEmpty(),
                    'materials_count' => $variant->materials->count(),
                    'quotation_info' => $this->getQuotationInfo($variant),
                ];
            });
    }

    private function getQuotationInfo(Variant $variant): ?array
    {
        $approved = $variant->approvedQuotation;
        if ($approved) {
            return [
                'version' => $approved->version_number,
                'total_gross' => $approved->total_gross,
                'is_approved' => true,
            ];
        }

        $latest = $variant->quotations()->orderByDesc('version_number')->first();
        if ($latest) {
            return [
                'version' => $latest->version_number,
                'total_gross' => $latest->total_gross,
                'is_approved' => false,
            ];
        }

        return null;
    }
}
