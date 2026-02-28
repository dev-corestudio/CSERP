<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Variant;
use App\Enums\OrderOverallStatus;
use App\Enums\PaymentStatus;
use App\Enums\VariantStatus;
use Carbon\Carbon;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            echo "Brak klientów w bazie. Uruchom najpierw CustomerSeeder.\n";
            return;
        }

        // Zamówienie 1 - ACME Corp
        $customer1 = $customers->where('name', 'ACME Corporation Sp. z o.o.')->first();
        if ($customer1) {
            $orderNumber = '1001';
            $order1 = Order::create([
                'customer_id' => $customer1->id,
                'order_number' => $orderNumber,
                'series' => Order::generateSeries($orderNumber),
                'description' => 'Stojaki displayowe do prezentacji produktów na targi.',
                'planned_delivery_date' => Carbon::now()->addDays(14),
                'overall_status' => OrderOverallStatus::PRODUCTION,
                'payment_status' => PaymentStatus::PARTIAL,
                'created_at' => Carbon::now()->subDays(15),
            ]);

            Variant::create([
                'order_id' => $order1->id,
                'variant_number' => 'A',
                'name' => 'Stojak Display 200cm - Czerwony',
                'quantity' => 50,
                'status' => VariantStatus::PRODUCTION,
            ]);

            Variant::create([
                'order_id' => $order1->id,
                'variant_number' => 'B',
                'name' => 'Stojak Display 150cm - Niebieski',
                'quantity' => 75,
                'status' => VariantStatus::PRODUCTION,
            ]);
        }

        // Zamówienie 2 - Tech Solutions
        $customer2 = $customers->where('name', 'Tech Solutions International')->first();
        if ($customer2) {
            $orderNumber = '1002';
            $order2 = Order::create([
                'customer_id' => $customer2->id,
                'order_number' => $orderNumber,
                'series' => Order::generateSeries($orderNumber),
                'description' => 'Mobilne szafki na 20 laptopów z ładowaniem.',
                'planned_delivery_date' => Carbon::now()->addDays(30),
                'overall_status' => OrderOverallStatus::QUOTATION,
                'payment_status' => PaymentStatus::UNPAID,
                'created_at' => Carbon::now()->subDays(10),
            ]);

            Variant::create([
                'order_id' => $order2->id,
                'variant_number' => 'A',
                'name' => 'Szafka mobilna 20 laptopów',
                'quantity' => 5,
                'status' => VariantStatus::QUOTATION,
            ]);
        }

        // Zamówienie 3 - Design Studio (SERIA)
// Symulacja: Klient domawia to samo, ten sam numer, nowa seria
        $customer3 = $customers->where('name', 'Design Studio Kreatywne')->first();
        if ($customer3) {
            // Pierwsza seria
            $orderNumber = '1003';
            $order3a = Order::create([
                'customer_id' => $customer3->id,
                'order_number' => $orderNumber,
                'series' => Order::generateSeries($orderNumber), // Powinno być 0001
                'description' => 'Modułowe regały ekspozycyjne do showroomu (Etap 1).',
                'planned_delivery_date' => Carbon::now()->subDays(5), // Już minęło
                'overall_status' => OrderOverallStatus::COMPLETED,
                'payment_status' => PaymentStatus::PAID,
                'created_at' => Carbon::now()->subDays(40),
            ]);

            // Druga seria (to samo ID projektu 1003)
            $order3b = Order::create([
                'customer_id' => $customer3->id,
                'order_number' => $orderNumber,
                'series' => Order::generateSeries($orderNumber), // Powinno być 0002
                'description' => 'Modułowe regały ekspozycyjne do showroomu (Domówienie).',
                'planned_delivery_date' => Carbon::now()->addDays(20),
                'overall_status' => OrderOverallStatus::PROTOTYPE,
                'payment_status' => PaymentStatus::UNPAID,
                'created_at' => Carbon::now()->subDays(5),
            ]);

            Variant::create([
                'order_id' => $order3b->id,
                'variant_number' => 'A',
                'name' => 'Regał modułowy 120x200cm',
                'quantity' => 10,
                'status' => VariantStatus::PRODUCTION,
            ]);
        }
    }
}
