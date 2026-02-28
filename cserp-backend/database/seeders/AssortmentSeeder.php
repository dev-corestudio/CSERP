<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assortment;
use App\Enums\AssortmentType;
use App\Enums\AssortmentUnit;

class AssortmentSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // MATERIAŁY
        // =====================================================================
        $materials = [
            // Płyty drewnopochodne
            ['name' => 'Płyta MDF 3mm', 'category' => 'Płyty MDF', 'unit' => AssortmentUnit::M2, 'default_price' => 18.00],
            ['name' => 'Płyta MDF 6mm', 'category' => 'Płyty MDF', 'unit' => AssortmentUnit::M2, 'default_price' => 28.00],
            ['name' => 'Płyta MDF 12mm', 'category' => 'Płyty MDF', 'unit' => AssortmentUnit::M2, 'default_price' => 38.00],
            ['name' => 'Płyta MDF 18mm', 'category' => 'Płyty MDF', 'unit' => AssortmentUnit::M2, 'default_price' => 48.00],

            ['name' => 'Sklejka brzozowa 4mm', 'category' => 'Sklejka', 'unit' => AssortmentUnit::M2, 'default_price' => 42.00],
            ['name' => 'Sklejka brzozowa 6mm', 'category' => 'Sklejka', 'unit' => AssortmentUnit::M2, 'default_price' => 52.00],
            ['name' => 'Sklejka brzozowa 12mm', 'category' => 'Sklejka', 'unit' => AssortmentUnit::M2, 'default_price' => 78.00],

            ['name' => 'Płyta wiórowa 18mm biała', 'category' => 'Płyty wiórowe', 'unit' => AssortmentUnit::M2, 'default_price' => 32.00],
            ['name' => 'Płyta wiórowa 18mm dąb', 'category' => 'Płyty wiórowe', 'unit' => AssortmentUnit::M2, 'default_price' => 38.00],

            // Pleksi i tworzywa
            ['name' => 'Pleksi bezbarwna 3mm', 'category' => 'Pleksi', 'unit' => AssortmentUnit::M2, 'default_price' => 110.00],
            ['name' => 'Pleksi bezbarwna 5mm', 'category' => 'Pleksi', 'unit' => AssortmentUnit::M2, 'default_price' => 165.00],
            ['name' => 'Pleksi mleczna 3mm', 'category' => 'Pleksi', 'unit' => AssortmentUnit::M2, 'default_price' => 125.00],
            ['name' => 'Pleksi czarna 3mm', 'category' => 'Pleksi', 'unit' => AssortmentUnit::M2, 'default_price' => 120.00],

            ['name' => 'Dibond biały 3mm', 'category' => 'Dibond', 'unit' => AssortmentUnit::M2, 'default_price' => 145.00],
            ['name' => 'Dibond czarny 3mm', 'category' => 'Dibond', 'unit' => AssortmentUnit::M2, 'default_price' => 150.00],

            ['name' => 'PCV spienione 3mm białe', 'category' => 'PCV', 'unit' => AssortmentUnit::M2, 'default_price' => 55.00],
            ['name' => 'PCV spienione 5mm białe', 'category' => 'PCV', 'unit' => AssortmentUnit::M2, 'default_price' => 75.00],

            // Drewno i Profile
            ['name' => 'Kantówka sosnowa 40x40mm', 'category' => 'Drewno', 'unit' => AssortmentUnit::MB, 'default_price' => 8.50],
            ['name' => 'Listwa dębowa 20x40mm', 'category' => 'Drewno', 'unit' => AssortmentUnit::MB, 'default_price' => 18.00],
            ['name' => 'Profil aluminiowy 20x20mm', 'category' => 'Aluminium', 'unit' => AssortmentUnit::MB, 'default_price' => 15.00],
            ['name' => 'Rura aluminiowa fi 25mm', 'category' => 'Aluminium', 'unit' => AssortmentUnit::MB, 'default_price' => 18.00],

            // Blachy
            ['name' => 'Blacha stalowa 1mm czarna', 'category' => 'Blacha', 'unit' => AssortmentUnit::M2, 'default_price' => 65.00],
            ['name' => 'Blacha nierdzewna 1mm', 'category' => 'Blacha', 'unit' => AssortmentUnit::M2, 'default_price' => 185.00],
            ['name' => 'Blacha aluminiowa 1mm', 'category' => 'Blacha', 'unit' => AssortmentUnit::M2, 'default_price' => 95.00],

            // Lakiery i farby (zakładamy, że l = litr -> np. opakowanie lub inna jednostka, tu użyję OP lub stworzę nową, ale użyjmy SZT lub OP jako litr)
            // Użyję AssortmentUnit::OP (opakowanie/litr) dla uproszczenia, lub dodaję L do enuma w poprzednim kroku.
            // W poprzednim kroku nie dodałem L, więc użyję SZT z opisem w nazwie, lub OP. Użyję SZT (1L).
            ['name' => 'Farba akrylowa biała mat 1L', 'category' => 'Lakiery', 'unit' => AssortmentUnit::SZT, 'default_price' => 48.00],
            ['name' => 'Lakier bezbarwny mat 1L', 'category' => 'Lakiery', 'unit' => AssortmentUnit::SZT, 'default_price' => 58.00],
            ['name' => 'Olej do drewna bezbarwny 1L', 'category' => 'Lakiery', 'unit' => AssortmentUnit::SZT, 'default_price' => 75.00],

            // Złączki i akcesoria
            ['name' => 'Wkręty 3.5x25mm (100szt)', 'category' => 'Złączki', 'unit' => AssortmentUnit::OP, 'default_price' => 12.00],
            ['name' => 'Konfirmaty 5x50mm (100szt)', 'category' => 'Złączki', 'unit' => AssortmentUnit::OP, 'default_price' => 25.00],
            ['name' => 'Zawias kubełkowy 35mm', 'category' => 'Złączki', 'unit' => AssortmentUnit::SZT, 'default_price' => 4.50],
            ['name' => 'Prowadnica kulkowa 450mm', 'category' => 'Złączki', 'unit' => AssortmentUnit::KPL, 'default_price' => 32.00],
            ['name' => 'Kółko meblowe fi50 z hamulcem', 'category' => 'Złączki', 'unit' => AssortmentUnit::SZT, 'default_price' => 12.00],

            // Kleje
            ['name' => 'Klej do drewna D3 1kg', 'category' => 'Kleje', 'unit' => AssortmentUnit::SZT, 'default_price' => 28.00],
            ['name' => 'Taśma dwustronna montażowa', 'category' => 'Kleje', 'unit' => AssortmentUnit::MB, 'default_price' => 2.50],

            // Elektronika
            ['name' => 'Taśma LED 12V ciepła', 'category' => 'Elektronika', 'unit' => AssortmentUnit::MB, 'default_price' => 12.00],
            ['name' => 'Zasilacz LED 12V 60W', 'category' => 'Elektronika', 'unit' => AssortmentUnit::SZT, 'default_price' => 38.00],

            // Materiały pomocnicze
            ['name' => 'Papier ścierny P120', 'category' => 'Materiały ścierne', 'unit' => AssortmentUnit::SZT, 'default_price' => 1.80],
            ['name' => 'Folia samoprzylepna biała mat', 'category' => 'Folie', 'unit' => AssortmentUnit::M2, 'default_price' => 25.00],

            // Opakowania
            ['name' => 'Karton klapowy 600x400x400', 'category' => 'Opakowania', 'unit' => AssortmentUnit::SZT, 'default_price' => 8.50],
            ['name' => 'Folia stretch 3kg', 'category' => 'Opakowania', 'unit' => AssortmentUnit::ROL, 'default_price' => 32.00],
            ['name' => 'Paleta EUR', 'category' => 'Opakowania', 'unit' => AssortmentUnit::SZT, 'default_price' => 45.00],
        ];

        // =====================================================================
        // USŁUGI
        // =====================================================================
        $services = [

            // Plotery-Laser
            ['name' => 'Frezowanie CNC', 'category' => 'Plotery-Laser', 'unit' => AssortmentUnit::H, 'default_price' => 180.00],
            ['name' => 'Laser CNC', 'category' => 'Plotery-Laser', 'unit' => AssortmentUnit::H, 'default_price' => 280.00],
            ['name' => 'Obróbka ręczna', 'category' => 'Plotery-Laser', 'unit' => AssortmentUnit::H, 'default_price' => 160.00],

            // Drukarnia
            ['name' => 'Drukowanie UV', 'category' => 'Drukarnia', 'unit' => AssortmentUnit::H, 'default_price' => 75.00],
            ['name' => 'Drukowanie solwent', 'category' => 'Drukarnia', 'unit' => AssortmentUnit::H, 'default_price' => 120.00],
            ['name' => 'Przygotowanie naklejek', 'category' => 'Drukarnia', 'unit' => AssortmentUnit::H, 'default_price' => 95.00],
            ['name' => 'Wyklejenie i obcinanie naklejek', 'category' => 'Drukarnia', 'unit' => AssortmentUnit::H, 'default_price' => 95.00],

            // Produkcja
            ['name' => 'Malowanie natryskowe', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Gięcie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Taśmowanie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Defoliowanie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Obdmuchanie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Polerowanie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Klejenie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Elektryka', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Montaż', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Pakowanie', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Termoformowanie MAŁE', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Termoformowanie DUŻE', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Obróbka ręczna', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Obcinanie wytłoczek', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Gradowanie wytłoczek', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Przygotowanie stanowiska', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Rozpakowanie metali', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Inne', 'category' => 'Produkcja', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],

            // Montaż
            ['name' => 'Montaż mebli', 'category' => 'Montaż', 'unit' => AssortmentUnit::H, 'default_price' => 85.00],
            ['name' => 'Montaż elektroniki/LED', 'category' => 'Montaż', 'unit' => AssortmentUnit::H, 'default_price' => 100.00],
            ['name' => 'Oklejanie folią', 'category' => 'Montaż', 'unit' => AssortmentUnit::H, 'default_price' => 90.00],

            // Pakowanie
            ['name' => 'Pakowanie i zabezpieczanie', 'category' => 'Pakowanie', 'unit' => AssortmentUnit::H, 'default_price' => 60.00],

            // Projektowanie
            ['name' => 'Projektowanie CAD/CAM', 'category' => 'Projektowanie', 'unit' => AssortmentUnit::H, 'default_price' => 120.00],

            // Transport
            ['name' => 'Dostawa lokalna', 'category' => 'Transport', 'unit' => AssortmentUnit::SZT, 'default_price' => 150.00],
            ['name' => 'Dostawa kurierem (paleta)', 'category' => 'Transport', 'unit' => AssortmentUnit::SZT, 'default_price' => 250.00],
        ];

        // Wstaw materiały
        foreach ($materials as $material) {
            Assortment::create([
                'type' => AssortmentType::MATERIAL,
                'name' => $material['name'],
                'category' => $material['category'],
                'unit' => $material['unit'],
                'default_price' => $material['default_price'],
                'description' => $material['description'] ?? $material['name'],
                'is_active' => true,
            ]);
        }

        echo " ✓ Utworzono " . count($materials) . " materiałów\n";

        // Wstaw usługi
        foreach ($services as $service) {
            Assortment::create([
                'type' => AssortmentType::SERVICE,
                'name' => $service['name'],
                'category' => $service['category'],
                'unit' => $service['unit'],
                'default_price' => $service['default_price'],
                'description' => $service['description'] ?? $service['name'],
                'is_active' => true,
            ]);
        }

        echo " ✓ Utworzono " . count($services) . " usług\n";
    }
}
