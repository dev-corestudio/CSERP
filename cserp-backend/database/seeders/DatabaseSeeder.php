<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║           CSERP - Inicjalizacja Bazy Danych                 ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            AssortmentSeeder::class,
            WorkstationSeeder::class,
            ProductionSeeder::class,
        ]);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              Seeding zakończony pomyślnie!                  ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }
}
