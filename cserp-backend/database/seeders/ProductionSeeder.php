<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Order,
    Variant,
    Quotation,
    QuotationItem,
    QuotationItemMaterial,
    QuotationItemService,
    Prototype,
    ProductionOrder,
    ProductionService,
    Invoice,
    Payment,
    Assortment,
    Customer,
    User,
    Workstation,
    Delivery
};
use App\Enums\{
    OrderOverallStatus,
    PaymentStatus,
    AssortmentType,
    ProductionStatus,
    WorkstationStatus,
    TestResult,
    InvoiceStatus,
    PaymentMethod,
    DeliveryStatus,
    WorkstationType,
    VariantStatus,
    VariantType
};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionSeeder extends Seeder
{
    private $materials;
    private $services;
    private $customers;
    private $managers;
    private $workers;
    private $workstations;

    // Liczniki
    private $orderCounter = 1000;
    private $deliveryCounter = 0;
    private $invoiceCounter = 0;
    private $year;

    public function run(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║ CSERP - Generowanie Danych Produkcyjnych (v2 - Variants)    ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->year = date('Y');
        $this->materials = Assortment::where('type', AssortmentType::MATERIAL)->get();
        $this->services = Assortment::where('type', AssortmentType::SERVICE)->get();
        $this->customers = Customer::all();
        $this->managers = User::where('role', \App\Enums\UserRole::PROJECT_MANAGER)->get();
        $this->workers = User::where('role', \App\Enums\UserRole::PRODUCTION_EMPLOYEE)->get();
        $this->workstations = Workstation::all();

        if ($this->customers->isEmpty() || $this->materials->isEmpty()) {
            echo "⚠️ BŁĄD: Brak danych bazowych! Uruchom CustomerSeeder i AssortmentSeeder.\n";
            return;
        }

        DB::beginTransaction();

        try {
            echo "🎯 Generowanie scenariuszy demonstracyjnych (1001-1005)...\n";

            // 1. ACME - Produkcja (ID 1001)
            $this->createCorporateOrder();

            // 2. EventMasters - Seria zamówień (ID 1002, Serie 0001 i 0002)
            $this->createEventSeriesOrder();

            // 3. Pilne zamówienie (ID 1003)
            $this->createUrgentOrder();

            // 4. Problematyczne (ID 1004) - PROTOTYP
            $this->createProblematicOrder();

            // 5. Wyceny (ID 1005)
            $this->createMultiQuotationOrder();

            // --- POPRAWKA: Przesunięcie licznika ---
            $this->orderCounter = 2000;

            echo "\n📊 Generowanie masowych danych (Zakończone i Anulowane)...\n";
            $this->createCompletedOrders(10);
            $this->createCancelledOrders(5);

            echo "\n🎲 Generowanie losowych aktywnych zamówień...\n";
            $this->generateMassOrders(50);

            DB::commit();

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║ ✅ SEEDING ZAKOŃCZONY POMYŚLNIE!                            ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n❌ BŁĄD: " . $e->getMessage() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "File: " . $e->getFile() . "\n";
            throw $e;
        }
    }

    // =========================================================================
    // SCENARIUSZE BIZNESOWE
    // =========================================================================

    private function createCorporateOrder(): void
    {
        $customer = $this->customers->where('name', 'ACME Corporation Sp. z o.o.')->first()
            ?? $this->customers->first();

        $num = '1001';

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => 'Stojaki displayowe premium do 50 salonów sprzedaży na terenie całej Polski. Wymagane logo firmy, podświetlenie LED, mobilność.',
            'planned_delivery_date' => Carbon::now()->addDays(20),
            'overall_status' => OrderOverallStatus::PRODUCTION,
            'payment_status' => PaymentStatus::PARTIAL,
            'created_at' => Carbon::now()->subDays(45),
        ]);

        // Wariant A - Stojaki duże (już w produkcji - zaawansowane)
        $variantA = $this->createVariant($order, 'A', 'Stojak Display 200cm - Premium', 50, VariantStatus::PRODUCTION, VariantType::SERIAL);
        $this->createApprovedQuotation($variantA, 95000);
        // Prototyp wewnętrzny zatwierdzony
        $this->createApprovedPrototype($variantA);
        $this->createActiveProduction($variantA, 60); // 60% completion

        // Wariant B - Stojaki średnie (startuje produkcja)
        $variantB = $this->createVariant($order, 'B', 'Stojak Display 150cm - Standard', 75, VariantStatus::PRODUCTION, VariantType::SERIAL);
        $this->createApprovedQuotation($variantB, 125000);
        $this->createApprovedPrototype($variantB);
        $this->createActiveProduction($variantB, 10); // 10% completion

        // Wariant C - Małe (czekają na start, faza prototypu)
        // Uwaga: Tutaj wariant jest typu SERIAL, ale status PROTOTYPE, bo trwa budowa prototypu przed serią
        $variantC = $this->createVariant($order, 'C', 'Stojak Display Counter-Top', 100, VariantStatus::PRODUCTION, VariantType::PROTOTYPE);
        $this->createApprovedQuotation($variantC, 55000);
        // Brak approved prototype -> czyli w trakcie
    }

    private function createEventSeriesOrder(): void
    {
        $customer = $this->customers->where('name', 'EventMasters Organizacja Eventów')->first()
            ?? $this->customers->random();

        $num = '1002';

        // Seria 1: Etap pierwszy (Zakończony)
        $order1 = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => 'Kompleksowa zabudowa stoiska targowego 6x4m (Etap 1: Konstrukcja).',
            'planned_delivery_date' => Carbon::now()->subDays(10),
            'overall_status' => OrderOverallStatus::COMPLETED,
            'payment_status' => PaymentStatus::PAID,
            'created_at' => Carbon::now()->subDays(60),
        ]);

        $variant1 = $this->createVariant($order1, 'A', 'Konstrukcja stalowa', 1, VariantStatus::COMPLETED, VariantType::SERIAL);
        $this->createApprovedQuotation($variant1, 45000);
        $this->createCompletedProduction($variant1);
        $this->createDelivery($variant1, DeliveryStatus::DELIVERED);
        $this->createInvoiceAndPayment($order1, 45000 * 1.23);

        // Seria 2: Domówienie (Ten sam numer 1002, seria 0002)
        $order2 = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0002',
            'description' => 'Kompleksowa zabudowa stoiska (Etap 2: Oświetlenie i Brandy). Domówienie do konstrukcji.',
            'planned_delivery_date' => Carbon::now()->addDays(15),
            'overall_status' => OrderOverallStatus::PROTOTYPE,
            'payment_status' => PaymentStatus::UNPAID,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Tu wariant jest prototypem w fazie produkcji
        $variant2 = $this->createVariant($order2, 'A', 'System LED i kasetony', 4, VariantStatus::PRODUCTION, VariantType::PROTOTYPE);
        $this->createApprovedQuotation($variant2, 22000);

        // Wariant oczekuje na decyzję klienta odnośnie prototypu
        $variant2->update(['is_approved' => false, 'feedback_notes' => 'Czekamy na akceptację jasności LED.']);
    }

    private function createUrgentOrder(): void
    {
        $customer = $this->customers->random();
        $num = '1003';

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => '[PILNE] Roll-upy reklamowe na targi za 2 tygodnie. Ekspresowa realizacja.',
            'planned_delivery_date' => Carbon::now()->addDays(5),
            'overall_status' => OrderOverallStatus::PRODUCTION,
            'payment_status' => PaymentStatus::UNPAID,
            'priority' => \App\Enums\OrderPriority::URGENT,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $variant = $this->createVariant($order, 'A', 'Roll-up Premium 85x200cm', 20, VariantStatus::PRODUCTION, VariantType::SERIAL);
        $this->createApprovedQuotation($variant, 12000);
        // Bez prototypu bo pilne
        $this->createActiveProduction($variant, 80);
    }

    private function createProblematicOrder(): void
    {
        $customer = $this->customers->random();
        $num = '1004';

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => 'Obudowy urządzeń medycznych - wymagana certyfikacja i precyzja wykonania.',
            'planned_delivery_date' => Carbon::now()->addDays(60),
            'overall_status' => OrderOverallStatus::PROTOTYPE,
            'payment_status' => PaymentStatus::UNPAID,
            'created_at' => Carbon::now()->subDays(60),
        ]);

        // Ten wariant to czysty PROTOTYP
        $variant = $this->createVariant($order, 'A', 'Obudowa urządzenia MED-01', 50, VariantStatus::PRODUCTION, VariantType::PROTOTYPE);

        // Wersja 3 zatwierdzona w systemie wycen
        $this->createApprovedQuotation($variant, 95000, 3);

        // Symulacja problemów z prototypami (stary system wciąż może być używany do historii)
        for ($i = 1; $i <= 3; $i++) {
            Prototype::create([
                'variant_id' => $variant->id,
                'version_number' => $i,
                'is_approved' => false,
                'test_result' => TestResult::FAILED,
                'feedback_notes' => "Prototyp v{$i}: Wymiary poza tolerancją ±0.1mm. Niezgodność z certyfikatem.",
                'sent_to_client_date' => Carbon::now()->subDays(60 - ($i * 10)),
                'client_response_date' => Carbon::now()->subDays(58 - ($i * 10)),
            ]);
        }

        // Aktualny stan wariantu
        $variant->update([
            'feedback_notes' => 'Czekamy na wyniki testów wersji v4.',
            'is_approved' => false
        ]);
    }

    private function createMultiQuotationOrder(): void
    {
        $customer = $this->customers->random();
        $num = '1005';

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => 'Meble biurowe do open space - 50 stanowisk. Negocjacje cenowe.',
            'planned_delivery_date' => Carbon::now()->addDays(40),
            'overall_status' => OrderOverallStatus::QUOTATION,
            'payment_status' => PaymentStatus::UNPAID,
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $variant = $this->createVariant($order, 'A', 'Biurko + kontener', 50, VariantStatus::QUOTATION, VariantType::SERIAL);

        // 5 wersji wycen
        for ($i = 1; $i <= 5; $i++) {
            $this->createQuotationForVariant($variant, $i, false, 180000 - ($i * 5000), Carbon::now()->subDays(20 - ($i * 2)));
        }
    }

    // =========================================================================
    // GENEROWANIE MASOWE
    // =========================================================================

    private function generateMassOrders(int $count): void
    {
        $statusDistribution = [
            OrderOverallStatus::DRAFT->value => 5,
            OrderOverallStatus::QUOTATION->value => 20,
            OrderOverallStatus::PROTOTYPE->value => 15,
            OrderOverallStatus::PRODUCTION->value => 40,
            OrderOverallStatus::DELIVERY->value => 20,
        ];

        for ($i = 0; $i < $count; $i++) {
            $rand = rand(1, 100);
            $cumulative = 0;
            $selectedStatus = OrderOverallStatus::QUOTATION;

            foreach ($statusDistribution as $status => $percentage) {
                $cumulative += $percentage;
                if ($rand <= $cumulative) {
                    $selectedStatus = OrderOverallStatus::from($status);
                    break;
                }
            }

            $this->createRandomOrder($selectedStatus);
        }
    }

    private function createRandomOrder(OrderOverallStatus $orderStatus): void
    {
        $customer = $this->customers->random();

        $this->orderCounter++;
        $num = str_pad((string) $this->orderCounter, 4, '0', STR_PAD_LEFT);

        $daysAgo = match ($orderStatus) {
            OrderOverallStatus::DRAFT => rand(1, 7),
            OrderOverallStatus::QUOTATION => rand(5, 30),
            OrderOverallStatus::PROTOTYPE => rand(20, 60),
            OrderOverallStatus::PRODUCTION => rand(30, 90),
            OrderOverallStatus::DELIVERY => rand(60, 100),
            default => 30
        };

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $num,
            'series' => '0001',
            'description' => $this->getRandomDescription(),
            'planned_delivery_date' => Carbon::now()->addDays(rand(10, 60)),
            'overall_status' => $orderStatus,
            'payment_status' => $this->getPaymentStatusForOrder($orderStatus),
            'created_at' => Carbon::now()->subDays($daysAgo),
        ]);

        // Mapowanie statusu zamówienia na status wariantu
        $variantStatus = match ($orderStatus) {
            OrderOverallStatus::DRAFT => VariantStatus::DRAFT,
            OrderOverallStatus::QUOTATION => VariantStatus::QUOTATION,
            OrderOverallStatus::PROTOTYPE => VariantStatus::PRODUCTION, // Prototyp jest "w produkcji"
            OrderOverallStatus::PRODUCTION => VariantStatus::PRODUCTION,
            OrderOverallStatus::DELIVERY => VariantStatus::DELIVERY,
            OrderOverallStatus::COMPLETED => VariantStatus::COMPLETED,
            OrderOverallStatus::CANCELLED => VariantStatus::CANCELLED,
        };

        $variantsCount = rand(1, 3);
        for ($j = 0; $j < $variantsCount; $j++) {
            $variant = $this->createVariant(
                $order,
                chr(65 + $j),
                'Produkt ' . chr(65 + $j),
                rand(5, 100),
                $variantStatus,
                VariantType::SERIAL
            );
            $this->populateVariantByStatus($variant, $variantStatus);
        }
    }

    private function createCompletedOrders(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $customer = $this->customers->random();
            $this->orderCounter++;
            $num = str_pad((string) $this->orderCounter, 4, '0', STR_PAD_LEFT);
            $daysAgo = rand(90, 365);

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => $num,
                'series' => '0001',
                'description' => $this->getRandomDescription(),
                'planned_delivery_date' => Carbon::now()->subDays($daysAgo - rand(5, 20)),
                'overall_status' => OrderOverallStatus::COMPLETED,
                'payment_status' => rand(0, 10) > 2 ? PaymentStatus::PAID : PaymentStatus::PARTIAL,
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);

            $variantsCount = rand(1, 2);
            $totalGross = 0;

            for ($j = 0; $j < $variantsCount; $j++) {
                $variant = $this->createVariant(
                    $order,
                    chr(65 + $j),
                    'Produkt ' . chr(65 + $j),
                    rand(5, 100),
                    VariantStatus::COMPLETED,
                    VariantType::SERIAL
                );

                $variantValue = rand(5000, 40000);
                $totalGross += $variantValue * 1.23;

                $this->createApprovedQuotation($variant, $variantValue);
                if (rand(0, 1)) {
                    // Czasami był prototyp
                    $this->createApprovedPrototype($variant);
                }
                $this->createCompletedProduction($variant);

                Delivery::create([
                    'variant_id' => $variant->id,
                    'delivery_number' => $this->generateDeliveryNumber(),
                    'delivery_date' => Carbon::now()->subDays($daysAgo - rand(10, 20)),
                    'status' => DeliveryStatus::DELIVERED,
                    'delivered_at' => Carbon::now()->subDays($daysAgo - rand(15, 25)),
                ]);
            }

            $this->createInvoiceAndPayment($order, $totalGross);
        }
    }

    private function createCancelledOrders(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $customer = $this->customers->random();
            $this->orderCounter++;
            $num = str_pad((string) $this->orderCounter, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => $num,
                'series' => '0001',
                'description' => $this->getRandomDescription() . ' [ANULOWANE]',
                'planned_delivery_date' => Carbon::now()->addDays(30),
                'overall_status' => OrderOverallStatus::CANCELLED,
                'payment_status' => PaymentStatus::UNPAID,
                'created_at' => Carbon::now()->subDays(rand(30, 100)),
            ]);

            $this->createVariant($order, 'A', 'Anulowany produkt', 10, VariantStatus::CANCELLED, VariantType::SERIAL);
        }
    }

    // =========================================================================
    // HELPERY - TWORZENIE OBIEKTÓW
    // =========================================================================

    private function populateVariantByStatus(Variant $variant, VariantStatus $status): void
    {
        if ($status === VariantStatus::DRAFT)
            return;

        // Wycena
        $approved = $status !== VariantStatus::QUOTATION; // Jeśli dalej niż quotation, to zatwierdzone
        $this->createQuotationForVariant($variant, 1, $approved, rand(3000, 50000));

        if ($status === VariantStatus::QUOTATION)
            return;

        // Prototyp (opcjonalny, tylko dla niektórych seryjnych lub jeśli to prototyp)
        if ($variant->type === VariantType::PROTOTYPE || rand(0, 10) > 6) {
            // Jeśli to seryjna z prototypem
            if ($variant->type === VariantType::SERIAL) {
                $this->createApprovedPrototype($variant);
            }
        }

        // Produkcja
        if ($status === VariantStatus::PRODUCTION) {
            // Losowy postęp produkcji
            $this->createActiveProduction($variant, rand(10, 80));
        } elseif (in_array($status, [VariantStatus::DELIVERY, VariantStatus::COMPLETED])) {
            $this->createCompletedProduction($variant);
        }

        // Dostawa
        if (in_array($status, [VariantStatus::DELIVERY, VariantStatus::COMPLETED])) {
            $this->createDelivery(
                $variant,
                $status === VariantStatus::COMPLETED ? DeliveryStatus::DELIVERED : DeliveryStatus::IN_TRANSIT
            );
        }
    }

    private function createVariant(Order $order, string $letter, string $name, int $quantity, VariantStatus $status, VariantType $type): Variant
    {
        return Variant::create([
            'order_id' => $order->id,
            'variant_number' => $letter,
            'name' => $name,
            'quantity' => $quantity,
            'status' => $status,
            'type' => $type,
            'description' => "Opis dla wariantu $name",
        ]);
    }

    private function createQuotationForVariant(Variant $variant, int $version, bool $approved, float $totalNet, ?Carbon $date = null): Quotation
    {
        $date = $date ?? Carbon::now()->subDays(rand(5, 20));

        $matCost = $totalNet * 0.4;
        $servCost = $totalNet * 0.4;
        $margin = $totalNet * 0.2;
        $gross = $totalNet * 1.23;

        $q = Quotation::create([
            'variant_id' => $variant->id,
            'version_number' => $version,
            'total_materials_cost' => $matCost,
            'total_services_cost' => $servCost,
            'total_net' => $totalNet,
            'total_gross' => $gross,
            'margin_percent' => 20,
            'is_approved' => $approved,
            'approved_at' => $approved ? $date : null,
            'approved_by_user_id' => $approved ? $this->managers->random()->id : null,
            'created_at' => $date,
        ]);

        $qItem = QuotationItem::create([
            'quotation_id' => $q->id,
            'materials_cost' => $matCost,
            'services_cost' => $servCost,
            'subtotal' => $matCost + $servCost,
        ]);

        // Materiał
        if ($this->materials->count() > 0) {
            $mat = $this->materials->random();
            QuotationItemMaterial::create([
                'quotation_item_id' => $qItem->id,
                'assortment_item_id' => $mat->id,
                'quantity' => 10,
                'unit' => 'szt',
                'unit_price' => $matCost / 10,
                'total_cost' => $matCost,
            ]);
        }

        // Usługa
        if ($this->services->count() > 0) {
            $srv = $this->services->random();
            QuotationItemService::create([
                'quotation_item_id' => $qItem->id,
                'assortment_item_id' => $srv->id,
                'estimated_quantity' => 10,
                'estimated_time_hours' => 5,
                'unit' => 'h',
                'unit_price' => $servCost / 5,
                'total_cost' => $servCost,
            ]);
        }

        return $q;
    }

    private function createApprovedQuotation(Variant $variant, float $totalNet, int $version = 1): void
    {
        $this->createQuotationForVariant($variant, $version, true, $totalNet);
    }

    private function createApprovedPrototype(Variant $variant, int $version = 1): void
    {
        // Tworzymy rekord w tabeli prototypes (dla historii)
        $proto = Prototype::create([
            'variant_id' => $variant->id,
            'version_number' => $version,
            'is_approved' => true,
            'test_result' => TestResult::PASSED,
            'feedback_notes' => 'Zatwierdzony.',
            'sent_to_client_date' => Carbon::now()->subDays(10),
            'client_response_date' => Carbon::now()->subDays(5),
        ]);

        // Aktualizujemy sam wariant
        $variant->update([
            'is_approved' => true,
            'approved_prototype_id' => $proto->id,
            'feedback_notes' => 'Zatwierdzony prototyp v' . $version
        ]);
    }

    private function createActiveProduction(Variant $variant, int $progressPercent): void
    {
        // Najpierw pobieramy koszt szacowany
        $quotation = $variant->quotations()->where('is_approved', true)->first();
        $estCost = $quotation ? $quotation->total_net : 10000;

        $po = ProductionOrder::create([
            'variant_id' => $variant->id,
            'quantity' => $variant->quantity,
            'total_estimated_cost' => $estCost,
            'total_actual_cost' => 0, // Zaktualizujemy po dodaniu zadań
            'status' => ProductionStatus::IN_PROGRESS,
            'started_at' => Carbon::now()->subDays(5),
        ]);

        // Generowanie zadań
        $this->createProductionTasks($po, ProductionStatus::IN_PROGRESS, $progressPercent);

        // Aktualizacja kosztu rzeczywistego
        $actualCost = $po->services()->sum('actual_cost') ?? 0;
        $po->update(['total_actual_cost' => $actualCost]);
    }

    private function createCompletedProduction(Variant $variant): void
    {
        $quotation = $variant->quotations()->where('is_approved', true)->first();
        $estCost = $quotation ? $quotation->total_net : 10000;

        $po = ProductionOrder::create([
            'variant_id' => $variant->id,
            'quantity' => $variant->quantity,
            'total_estimated_cost' => $estCost,
            'total_actual_cost' => 0,
            'status' => ProductionStatus::COMPLETED,
            'started_at' => Carbon::now()->subDays(20),
            'completed_at' => Carbon::now()->subDays(5),
        ]);

        $this->createProductionTasks($po, ProductionStatus::COMPLETED, 100);

        $actualCost = $po->services()->sum('actual_cost') ?? 0;
        $po->update(['total_actual_cost' => $actualCost]);
    }

    private function createProductionTasks(ProductionOrder $po, ProductionStatus $status, int $completionPercent): void
    {
        $allSteps = [
            ['name' => 'Cięcie materiału', 'type' => WorkstationType::LASER, 'hours' => rand(2, 8)],
            ['name' => 'Frezowanie CNC', 'type' => WorkstationType::CNC, 'hours' => rand(3, 12)],
            ['name' => 'Gięcie', 'type' => WorkstationType::PRODUCTION, 'hours' => rand(2, 6)],
            ['name' => 'Drukowanie', 'type' => WorkstationType::PRINTING, 'hours' => rand(1, 5)],
            ['name' => 'Montaż', 'type' => WorkstationType::ASSEMBLY, 'hours' => rand(5, 20)],
            ['name' => 'Malowanie', 'type' => WorkstationType::PAINTING, 'hours' => rand(2, 8)],
            ['name' => 'Pakowanie', 'type' => WorkstationType::OTHER, 'hours' => rand(1, 4)],
        ];

        // Wybieramy losowe 3-6 kroków
        $tasksCount = rand(3, 6);
        $selectedSteps = array_slice($allSteps, 0, $tasksCount);

        foreach ($selectedSteps as $idx => $step) {
            $ws = $this->workstations->where('type', $step['type'])->shuffle()->first()
                ?? $this->workstations->random();

            $worker = null;
            if ($this->workers->isNotEmpty()) {
                $assignedWorkers = $ws->operators;
                $worker = $assignedWorkers->isNotEmpty()
                    ? $assignedWorkers->random()
                    : $this->workers->random();
            }

            $stepProgressThreshold = ($idx + 1) * (100 / $tasksCount);

            $taskStatus = ProductionStatus::PLANNED;
            if ($status === ProductionStatus::COMPLETED) {
                $taskStatus = ProductionStatus::COMPLETED;
            } elseif ($stepProgressThreshold <= $completionPercent) {
                $taskStatus = ProductionStatus::COMPLETED;
            } elseif (($stepProgressThreshold - (100 / $tasksCount)) < $completionPercent) {
                $taskStatus = ProductionStatus::IN_PROGRESS;
            }

            $actualHours = null;
            $variancePercent = null;
            $unitPrice = rand(80, 150);

            if ($taskStatus === ProductionStatus::COMPLETED) {
                $variancePercent = rand(-15, 25);
                $actualHours = $step['hours'] * (1 + ($variancePercent / 100));
            } elseif ($taskStatus === ProductionStatus::IN_PROGRESS) {
                $actualHours = $step['hours'] * 0.5; // Połowa czasu
            }

            $baseDate = $po->started_at ? Carbon::parse($po->started_at) : Carbon::now();
            $plannedStart = $baseDate->copy()->addDays($idx);
            $plannedEnd = $baseDate->copy()->addDays($idx)->addHours($step['hours']);

            $actualStart = null;
            $actualEnd = null;

            if ($taskStatus !== ProductionStatus::PLANNED) {
                $actualStart = $plannedStart->copy()->addMinutes(rand(-30, 60));
                if ($taskStatus === ProductionStatus::COMPLETED) {
                    $actualEnd = $actualStart->copy()->addHours($actualHours);
                }
            }

            ProductionService::create([
                'production_order_id' => $po->id,
                'step_number' => $idx + 1,
                'service_name' => $step['name'],
                'workstation_id' => $ws->id,
                'assigned_to_user_id' => ($taskStatus !== ProductionStatus::PLANNED && $worker) ? $worker->id : null,
                'estimated_quantity' => $po->quantity,
                'estimated_time_hours' => $step['hours'],
                'unit_price' => $unitPrice,
                'estimated_cost' => $step['hours'] * $unitPrice,
                'actual_quantity' => ($taskStatus === ProductionStatus::COMPLETED) ? $po->quantity : null,
                'actual_time_hours' => $actualHours,
                'actual_cost' => $actualHours ? ($actualHours * $unitPrice) : null,
                'time_variance_hours' => $actualHours ? round($actualHours - $step['hours'], 2) : null,
                'variance_percent' => $variancePercent,
                'status' => $taskStatus,
                'planned_start_date' => $plannedStart,
                'planned_end_date' => $plannedEnd,
                'actual_start_date' => $actualStart,
                'actual_end_date' => $actualEnd,
            ]);

            if ($taskStatus === ProductionStatus::IN_PROGRESS) {
                $ws->update(['status' => WorkstationStatus::ACTIVE, 'current_task_id' => DB::getPdo()->lastInsertId()]);
            }
        }
    }

    private function createDelivery(Variant $variant, DeliveryStatus $status): void
    {
        $this->deliveryCounter++;
        Delivery::create([
            'variant_id' => $variant->id,
            'delivery_number' => 'DOW/' . $this->year . '/' . str_pad((string) $this->deliveryCounter, 4, '0', STR_PAD_LEFT),
            'delivery_date' => Carbon::now()->subDays(2),
            'tracking_number' => 'TRK' . rand(100000, 999999),
            'courier' => 'DHL',
            'status' => $status,
            'delivered_at' => $status === DeliveryStatus::DELIVERED ? Carbon::now()->subDays(1) : null,
        ]);
    }

    private function createInvoiceAndPayment(Order $order, float $amount): void
    {
        $this->invoiceCounter++;
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'FV/' . $this->year . '/' . str_pad((string) $this->invoiceCounter, 4, '0', STR_PAD_LEFT),
            'total_net' => $amount / 1.23,
            'total_gross' => $amount,
            'issue_date' => Carbon::now()->subDays(30),
            'payment_deadline' => Carbon::now()->subDays(16),
            'status' => InvoiceStatus::PAID,
            'paid_at' => Carbon::now()->subDays(10),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_date' => Carbon::now()->subDays(10),
            'payment_method' => PaymentMethod::TRANSFER,
            'transaction_id' => 'TRX-' . uniqid(),
        ]);
    }

    private function generateDeliveryNumber(): string
    {
        $this->deliveryCounter++;
        return 'DOW/' . $this->year . '/' . str_pad((string) $this->deliveryCounter, 4, '0', STR_PAD_LEFT);
    }

    private function getRandomDescription(): string
    {
        $desc = [
            'Stojaki ekspozycyjne na kosmetyki premium.',
            'Display counter-top z podświetleniem LED.',
            'Meble recepcyjne do lobby hotelowego.',
            'Regały magazynowe modułowe, wysokość 3m.',
            'Standy reklamowe trójstronne na targi.',
            'Zabudowa stoiska targowego 4x3m.',
            'Witryny chłodnicze - elementy ozdobne.',
            'Gondole sklepowe z haczykami na akcesoria.',
            'Lada kasowa z szufladami i zamkiem.',
            'Totemy reklamowe z wymiennymi plakatami.',
            'Meble do showroomu samochodowego.',
            'Displaye na okulary - wirujące.',
            'Standy na biżuterię ze szkła i metalu.',
            'Regały na wino, pojemność 50 butelek.',
            'Zabudowa punktu informacyjnego w galerii.',
            'Elementy scenografii do eventu firmowego.',
            'Konstrukcje reklamowe wolnostojące.',
            'Skrzynki transportowe drewniane na wymiar.',
            'Obudowy urządzeń elektronicznych.',
            'Roll-upy premium 85x200cm, druk UV.',
            'Banery wielkoformatowe z systemem montażu.',
            'Tablice informacyjne aluminiowe.',
            'Pudełka ozdobne z grawerem laserowym.',
            'Szyldy firmowe podświetlane LED.',
            'Stojaki na rowery miejskie, ocynkowane.',
        ];

        return $desc[array_rand($desc)];
    }

    private function getPaymentStatusForOrder(OrderOverallStatus $status): PaymentStatus
    {
        return match ($status) {
            OrderOverallStatus::PRODUCTION => rand(0, 10) > 7 ? PaymentStatus::PARTIAL : PaymentStatus::UNPAID,
            OrderOverallStatus::DELIVERY => rand(0, 10) > 5 ? PaymentStatus::PARTIAL : PaymentStatus::UNPAID,
            OrderOverallStatus::COMPLETED => rand(0, 10) > 3 ? PaymentStatus::PAID : PaymentStatus::PARTIAL,
            OrderOverallStatus::CANCELLED => PaymentStatus::UNPAID,
            default => PaymentStatus::UNPAID,
        };
    }
}
