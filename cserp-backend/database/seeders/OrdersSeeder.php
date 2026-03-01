<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Variant;
use App\Enums\ProjectOverallStatus;
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

        // Projekt 1 - ACME Corp
        $customer1 = $customers->where('name', 'ACME Corporation Sp. z o.o.')->first();
        if ($customer1) {
            $projectNumber = '1001';
            $project1 = Project::create([
                'customer_id' => $customer1->id,
                'project_number' => $projectNumber,
                'series' => Project::generateSeries($projectNumber),
                'description' => 'Stojaki displayowe do prezentacji produktów na targi.',
                'planned_delivery_date' => Carbon::now()->addDays(14),
                'overall_status' => ProjectOverallStatus::PRODUCTION,
                'payment_status' => PaymentStatus::PARTIAL,
                'created_at' => Carbon::now()->subDays(15),
            ]);

            Variant::create([
                'project_id' => $project1->id,
                'variant_number' => 'A',
                'name' => 'Stojak Display 200cm - Czerwony',
                'quantity' => 50,
                'status' => VariantStatus::PRODUCTION,
            ]);

            Variant::create([
                'project_id' => $project1->id,
                'variant_number' => 'B',
                'name' => 'Stojak Display 150cm - Niebieski',
                'quantity' => 75,
                'status' => VariantStatus::PRODUCTION,
            ]);
        }

        // Projekt 2 - Tech Solutions
        $customer2 = $customers->where('name', 'Tech Solutions International')->first();
        if ($customer2) {
            $projectNumber = '1002';
            $project2 = Project::create([
                'customer_id' => $customer2->id,
                'project_number' => $projectNumber,
                'series' => Project::generateSeries($projectNumber),
                'description' => 'Mobilne szafki na 20 laptopów z ładowaniem.',
                'planned_delivery_date' => Carbon::now()->addDays(30),
                'overall_status' => ProjectOverallStatus::QUOTATION,
                'payment_status' => PaymentStatus::UNPAID,
                'created_at' => Carbon::now()->subDays(10),
            ]);

            Variant::create([
                'project_id' => $project2->id,
                'variant_number' => 'A',
                'name' => 'Szafka mobilna 20 laptopów',
                'quantity' => 5,
                'status' => VariantStatus::QUOTATION,
            ]);
        }

        // Projekt 3 - Design Studio (SERIA)
        $customer3 = $customers->where('name', 'Design Studio Kreatywne')->first();
        if ($customer3) {
            // Pierwsza seria
            $projectNumber = '1003';
            $project3a = Project::create([
                'customer_id' => $customer3->id,
                'project_number' => $projectNumber,
                'series' => Project::generateSeries($projectNumber), // 0001
                'description' => 'Modułowe regały ekspozycyjne do showroomu (Etap 1).',
                'planned_delivery_date' => Carbon::now()->subDays(5),
                'overall_status' => ProjectOverallStatus::COMPLETED,
                'payment_status' => PaymentStatus::PAID,
                'created_at' => Carbon::now()->subDays(40),
            ]);

            // Druga seria (to samo ID projektu 1003)
            $project3b = Project::create([
                'customer_id' => $customer3->id,
                'project_number' => $projectNumber,
                'series' => Project::generateSeries($projectNumber), // 0002
                'description' => 'Modułowe regały ekspozycyjne do showroomu (Domówienie).',
                'planned_delivery_date' => Carbon::now()->addDays(20),
                'overall_status' => ProjectOverallStatus::PROTOTYPE,
                'payment_status' => PaymentStatus::UNPAID,
                'created_at' => Carbon::now()->subDays(5),
            ]);

            Variant::create([
                'project_id' => $project3b->id,
                'variant_number' => 'A',
                'name' => 'Regał modułowy 120x200cm',
                'quantity' => 10,
                'status' => VariantStatus::PRODUCTION,
            ]);
        }
    }
}
