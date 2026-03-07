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
use App\Enums\VariantType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Enums\PaymentStatus;
use App\Enums\ProjectOverallStatus;
use App\Enums\ProjectPriority;


/**
 * SeriesService — logika tworzenia nowych serii projektów
 */
class SeriesService
{
    // =========================================================================
    // TWORZENIE NOWEJ SERII
    // =========================================================================

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
    // KOPIOWANIE WARIANTU LUB GRUPY
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
            'childVariants.quotations.items.materials',
            'childVariants.quotations.items.services',
            'childVariants.materials',
        ])->findOrFail($sourceVariantId);

        $sourceProject = $sourceVariant->project;
        if ($sourceProject->project_number !== $targetProject->project_number) {
            throw new \InvalidArgumentException(
                "Wariant #{$sourceVariantId} należy do projektu " .
                "{$sourceProject->full_project_number}, " .
                "które ma inny numer niż docelowe {$targetProject->full_project_number}."
            );
        }

        if ($sourceVariant->is_group) {
            return $this->copyGroupWithChildren($sourceVariant, $targetProject, $copyQuotation, $copyMaterials);
        }

        $nextVariantNumber = $this->getNextVariantNumber($targetProject);

        $newVariant = Variant::create([
            'project_id' => $targetProject->id,
            'parent_variant_id' => null,
            'is_group' => false,
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
    // KOPIOWANIE GRUPY Z DZIEĆMI
    // =========================================================================

    /**
     * Kopiuje grupę wraz ze wszystkimi jej dziećmi (rekurencyjnie).
     * Zachowuje strukturę numeracji: stary prefiks (np. "A") → nowy (np. "B").
     */
    private function copyGroupWithChildren(
        Variant $sourceGroup,
        Project $targetProject,
        bool $copyQuotation,
        bool $copyMaterials
    ): Variant {
        $newLetter = $this->getNextVariantNumber($targetProject);
        $oldPrefix = $sourceGroup->variant_number;

        $newGroup = Variant::create([
            'project_id' => $targetProject->id,
            'parent_variant_id' => null,
            'is_group' => true,
            'variant_number' => $newLetter,
            'name' => $sourceGroup->name,
            'description' => $sourceGroup->description,
            'quantity' => 0,
            'type' => VariantType::SERIAL,
            'status' => VariantStatus::DRAFT,
            'is_approved' => false,
        ]);

        Log::info(
            "SeriesService: Skopiowano grupę #{$sourceGroup->id} ({$sourceGroup->variant_number}) " .
            "→ #{$newGroup->id} ({$newLetter}) w projekcie #{$targetProject->id}"
        );

        foreach ($sourceGroup->childVariants as $child) {
            $this->copyChildVariant(
                $child, $newGroup, $targetProject,
                $oldPrefix, $newLetter, $copyQuotation, $copyMaterials
            );
        }

        return $newGroup->fresh(['childVariants']);
    }

    /**
     * Kopiuje dziecko grupy pod nowego rodzica, zachowując sufiks numeru.
     * Przykład: stary "A1" → nowy "B1" (oldPrefix="A", newPrefix="B").
     * Działa rekurencyjnie dla poddzieci (A1_1 → B1_1).
     */
    private function copyChildVariant(
        Variant $source,
        Variant $newParent,
        Project $targetProject,
        string $oldPrefix,
        string $newPrefix,
        bool $copyQuotation,
        bool $copyMaterials
    ): Variant {
        $suffix = substr($source->variant_number, strlen($oldPrefix));
        $newNumber = $newPrefix . $suffix;

        $newVariant = Variant::create([
            'project_id' => $targetProject->id,
            'parent_variant_id' => $newParent->id,
            'is_group' => false,
            'variant_number' => $newNumber,
            'name' => $source->name,
            'description' => $source->description,
            'quantity' => $source->quantity,
            'type' => $source->type,
            'status' => VariantStatus::QUOTATION,
            'is_approved' => false,
            'feedback_notes' => null,
            'approved_prototype_id' => null,
        ]);

        if ($copyQuotation) {
            $this->copyBestQuotation($source, $newVariant);
        }

        if ($copyMaterials) {
            $this->copyVariantMaterials($source, $newVariant);
        }

        // Rekurencyjnie kopiuj poddzieci
        $source->load('childVariants.quotations.items.materials', 'childVariants.quotations.items.services', 'childVariants.materials');
        foreach ($source->childVariants as $grandChild) {
            $this->copyChildVariant(
                $grandChild, $newVariant, $targetProject,
                $oldPrefix, $newPrefix, $copyQuotation, $copyMaterials
            );
        }

        return $newVariant;
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

    /**
     * Zwraca tylko top-level warianty (grupy i standalone) do selektora kopiowania.
     * Grupy zwracają zagregowane info o dzieciach (czy mają wyceny/materiały).
     */
    public function getVariantsForCopySelector(Project $project)
    {
        $variants = Variant::with([
            'approvedQuotation',
            'materials',
            'childVariants.materials',
            'childVariants.quotations',
        ])
            ->where('project_id', $project->id)
            ->whereNull('parent_variant_id')
            ->orderBy('variant_number')
            ->get();

        return $variants->map(function (Variant $variant) {
            if ($variant->is_group) {
                $children = $variant->childVariants;
                $childrenHaveQuotation = $children->some(fn($c) => $c->quotations->isNotEmpty());
                $childrenHaveMaterials = $children->some(fn($c) => $c->materials->isNotEmpty());
                $totalMaterials = $children->sum(fn($c) => $c->materials->count());

                return [
                    'id' => $variant->id,
                    'variant_number' => $variant->variant_number,
                    'name' => $variant->name,
                    'is_group' => true,
                    'children_count' => $children->count(),
                    'has_quotation' => $childrenHaveQuotation,
                    'has_materials' => $childrenHaveMaterials,
                    'materials_count' => $totalMaterials,
                    'quantity' => null,
                    'type' => null,
                    'quotation_info' => null,
                ];
            }

            return [
                'id' => $variant->id,
                'variant_number' => $variant->variant_number,
                'name' => $variant->name,
                'is_group' => false,
                'children_count' => 0,
                'quantity' => $variant->quantity,
                'status' => $variant->status,
                'type' => $variant->type,
                'has_quotation' => $variant->quotations()->exists(),
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
