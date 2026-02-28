#!/bin/bash

##############################################################################
# CSERP - Kompletne Rozbudowane Seedery dla Wszystkich Modeli
##############################################################################

BACKEND_DIR="./cserp-backend"

cd "$BACKEND_DIR" || exit 1

echo "🔧 Tworzę rozbudowane seedery dla wszystkich modeli..."

# =============================================================================
# 1. UserSeeder - więcej pracowników
# =============================================================================
echo "📝 Aktualizuję UserSeeder..."

cat > database/seeders/UserSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Administracja
            ['name' => 'Administrator Systemu', 'email' => 'admin@cserp.pl', 'role' => 'admin'],
            ['name' => 'Backup Admin', 'email' => 'backup.admin@cserp.pl', 'role' => 'admin'],
            
            // Managerowie
            ['name' => 'Tomasz Kierownik', 'email' => 'tomasz.kierownik@cserp.pl', 'role' => 'manager'],
            ['name' => 'Ewa Produkcja', 'email' => 'ewa.produkcja@cserp.pl', 'role' => 'manager'],
            ['name' => 'Marek Sprzedaż', 'email' => 'marek.sprzedaz@cserp.pl', 'role' => 'manager'],
            
            // Pracownicy - Laser
            ['name' => 'Jan Kowalski', 'email' => 'jan.kowalski@cserp.pl', 'role' => 'worker'],
            ['name' => 'Piotr Nowak', 'email' => 'piotr.nowak@cserp.pl', 'role' => 'worker'],
            ['name' => 'Krzysztof Wiśniewski', 'email' => 'krzysztof.wisniewski@cserp.pl', 'role' => 'worker'],
            
            // Pracownicy - CNC
            ['name' => 'Anna Zielińska', 'email' => 'anna.zielinska@cserp.pl', 'role' => 'worker'],
            ['name' => 'Michał Wójcik', 'email' => 'michal.wojcik@cserp.pl', 'role' => 'worker'],
            ['name' => 'Kamil Kowalczyk', 'email' => 'kamil.kowalczyk@cserp.pl', 'role' => 'worker'],
            
            // Pracownicy - Montaż
            ['name' => 'Robert Kamiński', 'email' => 'robert.kaminski@cserp.pl', 'role' => 'worker'],
            ['name' => 'Dawid Lewandowski', 'email' => 'dawid.lewandowski@cserp.pl', 'role' => 'worker'],
            ['name' => 'Łukasz Dąbrowski', 'email' => 'lukasz.dabrowski@cserp.pl', 'role' => 'worker'],
            
            // Pracownicy - Malarnia
            ['name' => 'Agnieszka Szymańska', 'email' => 'agnieszka.szymanska@cserp.pl', 'role' => 'worker'],
            ['name' => 'Magdalena Woźniak', 'email' => 'magdalena.wozniak@cserp.pl', 'role' => 'worker'],
            
            // Pracownicy - Pakowanie
            ['name' => 'Paweł Kozłowski', 'email' => 'pawel.kozlowski@cserp.pl', 'role' => 'worker'],
            ['name' => 'Grzegorz Jankowski', 'email' => 'grzegorz.jankowski@cserp.pl', 'role' => 'worker'],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => $userData['role'],
                'is_active' => true,
            ]);
        }
        
        echo "   ✓ Utworzono " . count($users) . " użytkowników\n";
    }
}
EOF

echo "✅ UserSeeder zaktualizowany"

# =============================================================================
# 2. CustomerSeeder - więcej klientów
# =============================================================================
echo "📝 Aktualizuję CustomerSeeder..."

cat > database/seeders/CustomerSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            // Duże firmy B2B
            [
                'name' => 'ACME Corporation Sp. z o.o.',
                'nip' => '1234567890',
                'email' => 'zamowienia@acme.pl',
                'phone' => '+48 22 123 45 67',
                'address' => 'ul. Przemysłowa 100, 02-232 Warszawa',
                'type' => 'B2B',
            ],
            [
                'name' => 'Tech Solutions International',
                'nip' => '9876543210',
                'email' => 'procurement@techsolutions.pl',
                'phone' => '+48 12 987 65 43',
                'address' => 'al. Technologiczna 50, 30-300 Kraków',
                'type' => 'B2B',
            ],
            [
                'name' => 'Global Retail Group S.A.',
                'nip' => '5551112223',
                'email' => 'zakupy@globalretail.pl',
                'phone' => '+48 61 555 11 22',
                'address' => 'ul. Handlowa 88, 60-600 Poznań',
                'type' => 'B2B',
            ],
            [
                'name' => 'Media House Polska',
                'nip' => '7778889990',
                'email' => 'produkcja@mediahouse.pl',
                'phone' => '+48 71 777 88 99',
                'address' => 'pl. Medialny 15, 50-500 Wrocław',
                'type' => 'B2B',
            ],
            [
                'name' => 'Exhibition Masters Sp. z o.o.',
                'nip' => '3334445556',
                'email' => 'projekty@exhimasters.pl',
                'phone' => '+48 32 333 44 55',
                'address' => 'ul. Targowa 99, 40-400 Katowice',
                'type' => 'B2B',
            ],
            
            // Średnie firmy B2B
            [
                'name' => 'Design Studio Kreatywne',
                'nip' => '1112223334',
                'email' => 'biuro@designstudio.pl',
                'phone' => '+48 58 111 22 33',
                'address' => 'ul. Artystyczna 25, 80-800 Gdańsk',
                'type' => 'B2B',
            ],
            [
                'name' => 'Marketing Pro Agency',
                'nip' => '4445556667',
                'email' => 'info@marketingpro.pl',
                'phone' => '+48 81 444 55 66',
                'address' => 'ul. Reklamowa 33, 20-200 Lublin',
                'type' => 'B2B',
            ],
            [
                'name' => 'Furniture Factory Meble',
                'nip' => '6667778889',
                'email' => 'produkcja@furniturefactory.pl',
                'phone' => '+48 91 666 77 88',
                'address' => 'ul. Stolarska 12, 70-700 Szczecin',
                'type' => 'B2B',
            ],
            [
                'name' => 'EventPro Organizacja Imprez',
                'nip' => '2223334445',
                'email' => 'eventy@eventpro.pl',
                'phone' => '+48 42 222 33 44',
                'address' => 'ul. Eventowa 7, 90-900 Łódź',
                'type' => 'B2B',
            ],
            [
                'name' => 'Sklepy Premium Network',
                'nip' => '8889990001',
                'email' => 'wyposazenie@premiumnetwork.pl',
                'phone' => '+48 85 888 99 00',
                'address' => 'al. Luksusowa 1, 15-150 Białystok',
                'type' => 'B2B',
            ],
            
            // Małe firmy B2B
            [
                'name' => 'Biuro Architektoniczne ArcDesign',
                'nip' => '1231231234',
                'email' => 'projekty@arcdesign.pl',
                'phone' => '+48 52 123 12 31',
                'address' => 'ul. Projektowa 5, 85-850 Bydgoszcz',
                'type' => 'B2B',
            ],
            [
                'name' => 'Foto Studio Professional',
                'nip' => '3213213214',
                'email' => 'studio@fotopro.pl',
                'phone' => '+48 89 321 32 13',
                'address' => 'ul. Fotograficzna 8, 10-100 Olsztyn',
                'type' => 'B2B',
            ],
            [
                'name' => 'Kawiarnia Specialty Coffee',
                'nip' => '4564564567',
                'email' => 'wnetrza@specialtycoffee.pl',
                'phone' => '+48 14 456 45 64',
                'address' => 'ul. Kawowa 3, 33-300 Tarnów',
                'type' => 'B2B',
            ],
            [
                'name' => 'Salon Fryzjerski Elegance',
                'nip' => '7897897890',
                'email' => 'salon@elegance.pl',
                'phone' => '+48 18 789 78 97',
                'address' => 'ul. Piękna 22, 34-400 Nowy Targ',
                'type' => 'B2B',
            ],
            [
                'name' => 'Restauracja Pod Dębem',
                'nip' => '1472583690',
                'email' => 'restauracja@poddebem.pl',
                'phone' => '+48 74 147 25 83',
                'address' => 'ul. Gastronomiczna 11, 58-500 Jelenia Góra',
                'type' => 'B2B',
            ],
            
            // Klienci B2C
            [
                'name' => 'Jan Kowalski',
                'nip' => null,
                'email' => 'jan.kowalski.priv@gmail.com',
                'phone' => '+48 500 123 456',
                'address' => 'ul. Domowa 5/12, 00-001 Warszawa',
                'type' => 'B2C',
            ],
            [
                'name' => 'Anna Nowak',
                'nip' => null,
                'email' => 'anna.nowak.home@wp.pl',
                'phone' => '+48 501 234 567',
                'address' => 'ul. Prywatna 10, 30-030 Kraków',
                'type' => 'B2C',
            ],
            [
                'name' => 'Piotr Wiśniewski',
                'nip' => null,
                'email' => 'piotr.wisniewski@onet.pl',
                'phone' => '+48 502 345 678',
                'address' => 'ul. Mieszkaniowa 15/8, 50-050 Wrocław',
                'type' => 'B2C',
            ],
            [
                'name' => 'Firma Jednoosobowa - Adam Malinowski',
                'nip' => '9639639636',
                'email' => 'adam.malinowski.firma@gmail.com',
                'phone' => '+48 503 456 789',
                'address' => 'ul. Przedsiębiorcza 7, 60-060 Poznań',
                'type' => 'B2C',
            ],
            [
                'name' => 'Hobby Maker - Tomasz Zieliński',
                'nip' => null,
                'email' => 'tomasz.hobby@interia.pl',
                'phone' => '+48 504 567 890',
                'address' => 'ul. Warsztatowa 3, 80-080 Gdańsk',
                'type' => 'B2C',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create(array_merge($customer, ['is_active' => true]));
        }
        
        echo "   ✓ Utworzono " . count($customers) . " klientów\n";
    }
}
EOF

echo "✅ CustomerSeeder zaktualizowany"

# =============================================================================
# 3. AssortmentSeeder - więcej materiałów i usług
# =============================================================================
echo "📝 Aktualizuję AssortmentSeeder..."

cat > database/seeders/AssortmentSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assortment;

class AssortmentSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // MATERIAŁY
        // =====================================================================
        $materials = [
            // Płyty drewnopochodne
            ['name' => 'Płyta MDF 3mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 18.00, 'description' => 'Płyta MDF standard 3mm'],
            ['name' => 'Płyta MDF 6mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 28.00, 'description' => 'Płyta MDF standard 6mm'],
            ['name' => 'Płyta MDF 12mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 38.00, 'description' => 'Płyta MDF standard 12mm'],
            ['name' => 'Płyta MDF 18mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 48.00, 'description' => 'Płyta MDF standard 18mm'],
            ['name' => 'Płyta MDF 25mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 62.00, 'description' => 'Płyta MDF gruby 25mm'],
            ['name' => 'Płyta MDF lakierowana biała 18mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 85.00, 'description' => 'MDF lakierowany jednostronnie'],
            ['name' => 'Płyta MDF lakierowana czarna 18mm', 'category' => 'Płyty MDF', 'unit' => 'm2', 'default_price' => 95.00, 'description' => 'MDF lakierowany jednostronnie czarny'],
            
            ['name' => 'Płyta HDF 3mm', 'category' => 'Płyty HDF', 'unit' => 'm2', 'default_price' => 22.00, 'description' => 'Płyta HDF twarda 3mm'],
            ['name' => 'Płyta HDF lakierowana biała 3mm', 'category' => 'Płyty HDF', 'unit' => 'm2', 'default_price' => 35.00, 'description' => 'HDF lakierowany biały'],
            
            ['name' => 'Sklejka brzozowa 4mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 42.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka brzozowa 6mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 52.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka brzozowa 9mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 65.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka brzozowa 12mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 78.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka brzozowa 15mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 92.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka brzozowa 18mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 105.00, 'description' => 'Sklejka brzozowa BB/CP'],
            ['name' => 'Sklejka topola 6mm', 'category' => 'Sklejka', 'unit' => 'm2', 'default_price' => 35.00, 'description' => 'Sklejka topolowa ekonomiczna'],
            
            ['name' => 'Płyta wiórowa 18mm biała', 'category' => 'Płyty wiórowe', 'unit' => 'm2', 'default_price' => 32.00, 'description' => 'Płyta wiórowa laminowana biała'],
            ['name' => 'Płyta wiórowa 18mm dąb sonoma', 'category' => 'Płyty wiórowe', 'unit' => 'm2', 'default_price' => 38.00, 'description' => 'Płyta wiórowa laminowana dąb'],
            ['name' => 'Płyta wiórowa 18mm orzech', 'category' => 'Płyty wiórowe', 'unit' => 'm2', 'default_price' => 40.00, 'description' => 'Płyta wiórowa laminowana orzech'],
            
            // Pleksi i tworzywa
            ['name' => 'Pleksi bezbarwna 2mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 85.00, 'description' => 'PMMA ekstrudowana bezbarwna'],
            ['name' => 'Pleksi bezbarwna 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 110.00, 'description' => 'PMMA ekstrudowana bezbarwna'],
            ['name' => 'Pleksi bezbarwna 5mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 165.00, 'description' => 'PMMA ekstrudowana bezbarwna'],
            ['name' => 'Pleksi bezbarwna 8mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 245.00, 'description' => 'PMMA ekstrudowana bezbarwna'],
            ['name' => 'Pleksi bezbarwna 10mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 320.00, 'description' => 'PMMA ekstrudowana bezbarwna'],
            ['name' => 'Pleksi mleczna 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 125.00, 'description' => 'PMMA opal/mleczna'],
            ['name' => 'Pleksi mleczna 5mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 180.00, 'description' => 'PMMA opal/mleczna'],
            ['name' => 'Pleksi biała 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 115.00, 'description' => 'PMMA biała nieprzeźroczysta'],
            ['name' => 'Pleksi czarna 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 120.00, 'description' => 'PMMA czarna błyszcząca'],
            ['name' => 'Pleksi czerwona 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 135.00, 'description' => 'PMMA czerwona transparentna'],
            ['name' => 'Pleksi lustrzana złota 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 280.00, 'description' => 'PMMA lustrzana złota'],
            ['name' => 'Pleksi lustrzana srebrna 3mm', 'category' => 'Pleksi', 'unit' => 'm2', 'default_price' => 260.00, 'description' => 'PMMA lustrzana srebrna'],
            
            ['name' => 'Dibond biały 3mm', 'category' => 'Dibond', 'unit' => 'm2', 'default_price' => 145.00, 'description' => 'Płyta kompozytowa aluminiowa biała'],
            ['name' => 'Dibond srebrny 3mm', 'category' => 'Dibond', 'unit' => 'm2', 'default_price' => 155.00, 'description' => 'Płyta kompozytowa aluminiowa srebrna'],
            ['name' => 'Dibond czarny 3mm', 'category' => 'Dibond', 'unit' => 'm2', 'default_price' => 150.00, 'description' => 'Płyta kompozytowa aluminiowa czarna'],
            
            ['name' => 'PCV spienione 3mm białe', 'category' => 'PCV', 'unit' => 'm2', 'default_price' => 55.00, 'description' => 'Forex białe'],
            ['name' => 'PCV spienione 5mm białe', 'category' => 'PCV', 'unit' => 'm2', 'default_price' => 75.00, 'description' => 'Forex białe grubsze'],
            ['name' => 'PCV spienione 10mm białe', 'category' => 'PCV', 'unit' => 'm2', 'default_price' => 125.00, 'description' => 'Forex grube do frezowania'],
            
            // Drewno lite
            ['name' => 'Kantówka sosnowa 40x40mm', 'category' => 'Drewno', 'unit' => 'mb', 'default_price' => 8.50, 'description' => 'Kantówka strugana sosna'],
            ['name' => 'Kantówka sosnowa 50x50mm', 'category' => 'Drewno', 'unit' => 'mb', 'default_price' => 12.00, 'description' => 'Kantówka strugana sosna'],
            ['name' => 'Kantówka sosnowa 60x60mm', 'category' => 'Drewno', 'unit' => 'mb', 'default_price' => 18.00, 'description' => 'Kantówka strugana sosna'],
            ['name' => 'Listwa sosnowa 20x40mm', 'category' => 'Drewno', 'unit' => 'mb', 'default_price' => 5.50, 'description' => 'Listwa strugana sosna'],
            ['name' => 'Listwa dębowa 20x40mm', 'category' => 'Drewno', 'unit' => 'mb', 'default_price' => 18.00, 'description' => 'Listwa strugana dąb'],
            ['name' => 'Deska dębowa 20mm', 'category' => 'Drewno', 'unit' => 'm2', 'default_price' => 320.00, 'description' => 'Deska dębowa klejona'],
            ['name' => 'Deska jesionowa 20mm', 'category' => 'Drewno', 'unit' => 'm2', 'default_price' => 280.00, 'description' => 'Deska jesionowa klejona'],
            
            // Profile aluminiowe
            ['name' => 'Profil aluminiowy 20x20mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 15.00, 'description' => 'Profil kwadratowy anodowany'],
            ['name' => 'Profil aluminiowy 30x30mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 22.00, 'description' => 'Profil kwadratowy anodowany'],
            ['name' => 'Profil aluminiowy 40x40mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 32.00, 'description' => 'Profil kwadratowy anodowany'],
            ['name' => 'Kątownik aluminiowy 20x20x2mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 8.00, 'description' => 'Kątownik anodowany'],
            ['name' => 'Kątownik aluminiowy 30x30x2mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 12.00, 'description' => 'Kątownik anodowany'],
            ['name' => 'Rura aluminiowa fi 25mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 18.00, 'description' => 'Rura okrągła anodowana'],
            ['name' => 'Rura aluminiowa fi 32mm', 'category' => 'Aluminium', 'unit' => 'mb', 'default_price' => 24.00, 'description' => 'Rura okrągła anodowana'],
            
            // Blachy
            ['name' => 'Blacha stalowa 1mm czarna', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 65.00, 'description' => 'Blacha zimnowalcowana'],
            ['name' => 'Blacha stalowa 2mm czarna', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 95.00, 'description' => 'Blacha zimnowalcowana'],
            ['name' => 'Blacha stalowa 3mm czarna', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 135.00, 'description' => 'Blacha zimnowalcowana'],
            ['name' => 'Blacha nierdzewna 1mm', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 185.00, 'description' => 'Inox 304 satyna'],
            ['name' => 'Blacha nierdzewna 2mm', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 285.00, 'description' => 'Inox 304 satyna'],
            ['name' => 'Blacha ocynkowana 1mm', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 75.00, 'description' => 'Blacha ocynkowana ogniowo'],
            ['name' => 'Blacha aluminiowa 1mm', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 95.00, 'description' => 'Blacha alu 1050'],
            ['name' => 'Blacha aluminiowa 2mm', 'category' => 'Blacha', 'unit' => 'm2', 'default_price' => 145.00, 'description' => 'Blacha alu 1050'],
            
            // Lakiery i farby
            ['name' => 'Farba akrylowa biała mat', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 48.00, 'description' => 'Farba do drewna i MDF'],
            ['name' => 'Farba akrylowa biała połysk', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 52.00, 'description' => 'Farba do drewna i MDF'],
            ['name' => 'Farba akrylowa czarna mat', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 55.00, 'description' => 'Farba do drewna i MDF'],
            ['name' => 'Farba RAL wg wzornika', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 85.00, 'description' => 'Farba barwiona wg RAL'],
            ['name' => 'Lakier bezbarwny mat', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 58.00, 'description' => 'Lakier akrylowy bezbarwny'],
            ['name' => 'Lakier bezbarwny połysk', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 62.00, 'description' => 'Lakier akrylowy bezbarwny'],
            ['name' => 'Bejca wodna dąb', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 42.00, 'description' => 'Bejca wodna do drewna'],
            ['name' => 'Bejca wodna orzech', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 42.00, 'description' => 'Bejca wodna do drewna'],
            ['name' => 'Olej do drewna bezbarwny', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 75.00, 'description' => 'Olej twardy do blatów'],
            ['name' => 'Primer/podkład uniwersalny', 'category' => 'Lakiery', 'unit' => 'l', 'default_price' => 38.00, 'description' => 'Podkład gruntujący'],
            
            // Złączki i akcesoria
            ['name' => 'Wkręty 3.5x25mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 12.00, 'description' => 'Wkręty do drewna PZ'],
            ['name' => 'Wkręty 4x40mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 15.00, 'description' => 'Wkręty do drewna PZ'],
            ['name' => 'Wkręty 4.5x50mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 18.00, 'description' => 'Wkręty do drewna PZ'],
            ['name' => 'Wkręty 5x60mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 22.00, 'description' => 'Wkręty do drewna PZ'],
            ['name' => 'Kołki rozporowe 6x40mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 18.00, 'description' => 'Kołki nylonowe z wkrętem'],
            ['name' => 'Konfirmaty 5x50mm (100szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 25.00, 'description' => 'Wkręty meblowe'],
            ['name' => 'Mimośród fi35 (10szt)', 'category' => 'Złączki', 'unit' => 'op', 'default_price' => 15.00, 'description' => 'Złączka mimośrodowa meblowa'],
            ['name' => 'Zawias kubełkowy 35mm', 'category' => 'Złączki', 'unit' => 'szt', 'default_price' => 4.50, 'description' => 'Zawias puszkowy z prowadnikiem'],
            ['name' => 'Prowadnica kulkowa 350mm', 'category' => 'Złączki', 'unit' => 'kpl', 'default_price' => 25.00, 'description' => 'Prowadnica szufladowa pełen wysuw'],
            ['name' => 'Prowadnica kulkowa 450mm', 'category' => 'Złączki', 'unit' => 'kpl', 'default_price' => 32.00, 'description' => 'Prowadnica szufladowa pełen wysuw'],
            ['name' => 'Nóżka meblowa 100mm regulowana', 'category' => 'Złączki', 'unit' => 'szt', 'default_price' => 8.00, 'description' => 'Nóżka plastikowa regulowana'],
            ['name' => 'Nóżka meblowa metalowa 150mm', 'category' => 'Złączki', 'unit' => 'szt', 'default_price' => 18.00, 'description' => 'Nóżka stalowa chromowana'],
            ['name' => 'Kółko meblowe fi50 z hamulcem', 'category' => 'Złączki', 'unit' => 'szt', 'default_price' => 12.00, 'description' => 'Kółko obrotowe z hamulcem'],
            
            // Kleje
            ['name' => 'Klej do drewna D3 1kg', 'category' => 'Kleje', 'unit' => 'szt', 'default_price' => 28.00, 'description' => 'Klej poliuretanowy wodoodporny'],
            ['name' => 'Klej do drewna D4 1kg', 'category' => 'Kleje', 'unit' => 'szt', 'default_price' => 42.00, 'description' => 'Klej dwuskładnikowy zewnętrzny'],
            ['name' => 'Klej montażowy biały 310ml', 'category' => 'Kleje', 'unit' => 'szt', 'default_price' => 18.00, 'description' => 'Klej montażowy w tubie'],
            ['name' => 'Klej na gorąco (1kg)', 'category' => 'Kleje', 'unit' => 'kg', 'default_price' => 35.00, 'description' => 'Klej w laskach fi11'],
            ['name' => 'Taśma dwustronna montażowa 19mm', 'category' => 'Kleje', 'unit' => 'mb', 'default_price' => 2.50, 'description' => 'Taśma piankowa gruba'],
            ['name' => 'Klej kontaktowy 1l', 'category' => 'Kleje', 'unit' => 'szt', 'default_price' => 55.00, 'description' => 'Klej do laminatów'],
            
            // Elektronika i oświetlenie
            ['name' => 'Taśma LED 12V ciepła 60LED/m', 'category' => 'Elektronika', 'unit' => 'm', 'default_price' => 12.00, 'description' => 'Taśma LED 3528 ciepła biel'],
            ['name' => 'Taśma LED 12V zimna 60LED/m', 'category' => 'Elektronika', 'unit' => 'm', 'default_price' => 12.00, 'description' => 'Taśma LED 3528 zimna biel'],
            ['name' => 'Taśma LED 12V RGB 60LED/m', 'category' => 'Elektronika', 'unit' => 'm', 'default_price' => 28.00, 'description' => 'Taśma LED 5050 RGB'],
            ['name' => 'Profil LED natynkowy z kloszem', 'category' => 'Elektronika', 'unit' => 'mb', 'default_price' => 18.00, 'description' => 'Profil aluminiowy do LED'],
            ['name' => 'Profil LED wpuszczany z kloszem', 'category' => 'Elektronika', 'unit' => 'mb', 'default_price' => 25.00, 'description' => 'Profil aluminiowy wpuszczany'],
            ['name' => 'Zasilacz LED 12V 30W', 'category' => 'Elektronika', 'unit' => 'szt', 'default_price' => 25.00, 'description' => 'Zasilacz impulsowy'],
            ['name' => 'Zasilacz LED 12V 60W', 'category' => 'Elektronika', 'unit' => 'szt', 'default_price' => 38.00, 'description' => 'Zasilacz impulsowy'],
            ['name' => 'Zasilacz LED 12V 100W', 'category' => 'Elektronika', 'unit' => 'szt', 'default_price' => 55.00, 'description' => 'Zasilacz impulsowy'],
            ['name' => 'Zasilacz LED 12V 150W wodoodporny', 'category' => 'Elektronika', 'unit' => 'szt', 'default_price' => 85.00, 'description' => 'Zasilacz IP67'],
            ['name' => 'Sterownik RGB + pilot', 'category' => 'Elektronika', 'unit' => 'kpl', 'default_price' => 35.00, 'description' => 'Kontroler RGB z pilotem IR'],
            ['name' => 'Złączka LED kątowa', 'category' => 'Elektronika', 'unit' => 'szt', 'default_price' => 3.50, 'description' => 'Złączka narożna do taśm'],
            ['name' => 'Przewód LED 2x0.5mm', 'category' => 'Elektronika', 'unit' => 'mb', 'default_price' => 1.50, 'description' => 'Przewód zasilający'],
            
            // Materiały pomocnicze
            ['name' => 'Papier ścierny P80 (arkusz)', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 1.80, 'description' => 'Papier ścierny gruboziarnisty'],
            ['name' => 'Papier ścierny P120 (arkusz)', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 1.80, 'description' => 'Papier ścierny średni'],
            ['name' => 'Papier ścierny P180 (arkusz)', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 2.00, 'description' => 'Papier ścierny drobny'],
            ['name' => 'Papier ścierny P240 (arkusz)', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 2.20, 'description' => 'Papier ścierny bardzo drobny'],
            ['name' => 'Gąbka ścierna P120', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 5.50, 'description' => 'Gąbka do szlifowania profili'],
            ['name' => 'Krążek ścierny fi125 P80', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 1.20, 'description' => 'Krążek na rzep'],
            ['name' => 'Krążek ścierny fi125 P120', 'category' => 'Materiały ścierne', 'unit' => 'szt', 'default_price' => 1.20, 'description' => 'Krążek na rzep'],
            
            // Folie i oklejanie
            ['name' => 'Folia samoprzylepna biała mat', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 25.00, 'description' => 'Folia do plotera mat'],
            ['name' => 'Folia samoprzylepna biała połysk', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 28.00, 'description' => 'Folia do plotera błysk'],
            ['name' => 'Folia samoprzylepna czarna mat', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 28.00, 'description' => 'Folia do plotera mat'],
            ['name' => 'Folia samoprzylepna druk', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 45.00, 'description' => 'Folia do druku UV'],
            ['name' => 'Laminat ochronny mat', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 18.00, 'description' => 'Laminat do zabezpieczania wydruków'],
            ['name' => 'Laminat ochronny połysk', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 18.00, 'description' => 'Laminat do zabezpieczania wydruków'],
            ['name' => 'Okleina meblowa dąb', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 55.00, 'description' => 'Okleina PCV imitacja dębu'],
            ['name' => 'Okleina meblowa biała', 'category' => 'Folie', 'unit' => 'm2', 'default_price' => 35.00, 'description' => 'Okleina PCV biała mat'],
            
            // Opakowania
            ['name' => 'Karton klapowy 600x400x400', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 8.50, 'description' => 'Karton 3W BC'],
            ['name' => 'Karton klapowy 400x300x300', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 5.50, 'description' => 'Karton 3W BC'],
            ['name' => 'Karton klapowy 300x200x200', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 3.50, 'description' => 'Karton 3W BC'],
            ['name' => 'Folia stretch 500mm 3kg', 'category' => 'Opakowania', 'unit' => 'rol', 'default_price' => 32.00, 'description' => 'Folia do paletowania'],
            ['name' => 'Folia bąbelkowa 1m (rolka 50m)', 'category' => 'Opakowania', 'unit' => 'rol', 'default_price' => 85.00, 'description' => 'Folia ochronna bąbelkowa'],
            ['name' => 'Pianka PE 3mm (rolka 50m2)', 'category' => 'Opakowania', 'unit' => 'rol', 'default_price' => 125.00, 'description' => 'Pianka ochronna'],
            ['name' => 'Narożnik kartonowy 50x50x1000', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 3.50, 'description' => 'Narożnik ochronny'],
            ['name' => 'Taśma pakowa brązowa 48mm', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 8.00, 'description' => 'Taśma klejąca pakowa'],
            ['name' => 'Paleta EUR 1200x800', 'category' => 'Opakowania', 'unit' => 'szt', 'default_price' => 45.00, 'description' => 'Paleta drewniana EUR'],
        ];

        // =====================================================================
        // USŁUGI
        // =====================================================================
        $services = [
            // Cięcie laserowe
            ['name' => 'Cięcie laserowe - drewno/MDF', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 150.00, 'description' => 'Cięcie laserowe CO2 materiałów drewnianych'],
            ['name' => 'Cięcie laserowe - pleksi do 5mm', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 180.00, 'description' => 'Cięcie laserowe CO2 pleksi cienka'],
            ['name' => 'Cięcie laserowe - pleksi 6-10mm', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 220.00, 'description' => 'Cięcie laserowe CO2 pleksi gruba'],
            ['name' => 'Cięcie laserowe - sklejka', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 160.00, 'description' => 'Cięcie laserowe CO2 sklejka'],
            ['name' => 'Cięcie laserowe - metal fiber', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 280.00, 'description' => 'Cięcie laserowe fiber blacha'],
            ['name' => 'Grawerowanie laserowe - płytkie', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 120.00, 'description' => 'Grawerowanie powierzchniowe'],
            ['name' => 'Grawerowanie laserowe - głębokie', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 160.00, 'description' => 'Grawerowanie wgłębne'],
            ['name' => 'Cięcie laserowe - filc/tkanina', 'category' => 'Laser', 'unit' => 'h', 'default_price' => 140.00, 'description' => 'Cięcie materiałów tekstylnych'],
            
            // CNC
            ['name' => 'Frezowanie CNC 2D - standard', 'category' => 'CNC', 'unit' => 'h', 'default_price' => 180.00, 'description' => 'Frezowanie płaskie proste'],
            ['name' => 'Frezowanie CNC 2.5D', 'category' => 'CNC', 'unit' => 'h', 'default_price' => 220.00, 'description' => 'Frezowanie z różnymi głębokościami'],
            ['name' => 'Frezowanie CNC 3D', 'category' => 'CNC', 'unit' => 'h', 'default_price' => 280.00, 'description' => 'Frezowanie przestrzenne'],
            ['name' => 'Wiercenie CNC', 'category' => 'CNC', 'unit' => 'h', 'default_price' => 100.00, 'description' => 'Wiercenie seryjne'],
            ['name' => 'Nestowanie CNC', 'category' => 'CNC', 'unit' => 'h', 'default_price' => 160.00, 'description' => 'Rozkrój optymalizowany'],
            ['name' => 'Okleinowanie CNC krawędzi', 'category' => 'CNC', 'unit' => 'mb', 'default_price' => 8.00, 'description' => 'Okleinowanie automatyczne'],
            
            // Obróbka ręczna
            ['name' => 'Szlifowanie ręczne', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 75.00, 'description' => 'Szlifowanie manualne'],
            ['name' => 'Szlifowanie maszynowe', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 90.00, 'description' => 'Szlifowanie szlifierką taśmową'],
            ['name' => 'Wiercenie ręczne', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 65.00, 'description' => 'Wiercenie wiertarką'],
            ['name' => 'Cięcie piłą tarczową', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 80.00, 'description' => 'Cięcie piłą stołową'],
            ['name' => 'Frezowanie ręczne', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 95.00, 'description' => 'Frezowanie frezarką górnowrzecionową'],
            ['name' => 'Gięcie pleksi', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 120.00, 'description' => 'Gięcie termiczne pleksi'],
            ['name' => 'Polerowanie pleksi', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 110.00, 'description' => 'Polerowanie krawędzi płomieniem'],
            ['name' => 'Klejenie pleksi', 'category' => 'Obróbka', 'unit' => 'h', 'default_price' => 95.00, 'description' => 'Klejenie elementów z pleksi'],
            
            // Malarnia
            ['name' => 'Gruntowanie', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 70.00, 'description' => 'Nakładanie podkładu'],
            ['name' => 'Malowanie - 1 warstwa', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 85.00, 'description' => 'Malowanie natryskowe jedna warstwa'],
            ['name' => 'Malowanie - 2 warstwy', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 120.00, 'description' => 'Malowanie natryskowe dwie warstwy'],
            ['name' => 'Malowanie - wysoki połysk', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 160.00, 'description' => 'Malowanie z polerowaniem'],
            ['name' => 'Lakierowanie bezbarwne', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 90.00, 'description' => 'Lakierowanie ochronne'],
            ['name' => 'Bejcowanie', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 75.00, 'description' => 'Barwienie drewna bejcą'],
            ['name' => 'Olejowanie', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 80.00, 'description' => 'Nakładanie oleju do drewna'],
            ['name' => 'Malowanie proszkowe', 'category' => 'Malarnia', 'unit' => 'h', 'default_price' => 140.00, 'description' => 'Malowanie proszkowe metalu'],
            
            // Montaż
            ['name' => 'Montaż elementów - prosty', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 70.00, 'description' => 'Skręcanie prostych konstrukcji'],
            ['name' => 'Montaż elementów - złożony', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 95.00, 'description' => 'Montaż skomplikowanych elementów'],
            ['name' => 'Montaż mebli', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 85.00, 'description' => 'Składanie mebli z elementów'],
            ['name' => 'Montaż elektroniki/LED', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 100.00, 'description' => 'Montaż oświetlenia LED'],
            ['name' => 'Oklejanie folią', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 90.00, 'description' => 'Aplikacja folii samoprzylepnej'],
            ['name' => 'Montaż szyb/luster', 'category' => 'Montaż', 'unit' => 'h', 'default_price' => 110.00, 'description' => 'Montaż elementów szklanych'],
            
            // Pakowanie
            ['name' => 'Pakowanie standardowe', 'category' => 'Pakowanie', 'unit' => 'h', 'default_price' => 50.00, 'description' => 'Pakowanie w kartony'],
            ['name' => 'Pakowanie premium', 'category' => 'Pakowanie', 'unit' => 'h', 'default_price' => 75.00, 'description' => 'Pakowanie z dodatkowymi zabezpieczeniami'],
            ['name' => 'Paletowanie', 'category' => 'Pakowanie', 'unit' => 'h', 'default_price' => 60.00, 'description' => 'Układanie na paletach i foliowanie'],
            ['name' => 'Pakowanie nietypowe', 'category' => 'Pakowanie', 'unit' => 'h', 'default_price' => 90.00, 'description' => 'Tworzenie opakowań na wymiar'],
            
            // Projektowanie
            ['name' => 'Projekt techniczny 2D', 'category' => 'Projektowanie', 'unit' => 'h', 'default_price' => 120.00, 'description' => 'Rysunki techniczne AutoCAD'],
            ['name' => 'Projekt techniczny 3D', 'category' => 'Projektowanie', 'unit' => 'h', 'default_price' => 150.00, 'description' => 'Modelowanie 3D SolidWorks'],
            ['name' => 'Wizualizacja 3D', 'category' => 'Projektowanie', 'unit' => 'h', 'default_price' => 180.00, 'description' => 'Rendering fotorealistyczny'],
            ['name' => 'Projekt graficzny', 'category' => 'Projektowanie', 'unit' => 'h', 'default_price' => 130.00, 'description' => 'Projekty graficzne do druku'],
            ['name' => 'Przygotowanie plików do produkcji', 'category' => 'Projektowanie', 'unit' => 'h', 'default_price' => 100.00, 'description' => 'Przygotowanie CAM'],
            
            // Transport i logistyka
            ['name' => 'Dostawa lokalna (do 30km)', 'category' => 'Transport', 'unit' => 'szt', 'default_price' => 150.00, 'description' => 'Dostawa własnym transportem'],
            ['name' => 'Dostawa regionalna (30-100km)', 'category' => 'Transport', 'unit' => 'szt', 'default_price' => 350.00, 'description' => 'Dostawa busem'],
            ['name' => 'Dostawa krajowa', 'category' => 'Transport', 'unit' => 'szt', 'default_price' => 650.00, 'description' => 'Dostawa kurierem paletowym'],
            ['name' => 'Montaż u klienta', 'category' => 'Transport', 'unit' => 'h', 'default_price' => 120.00, 'description' => 'Montaż na miejscu'],
            ['name' => 'Odbiór własny - obsługa', 'category' => 'Transport', 'unit' => 'szt', 'default_price' => 50.00, 'description' => 'Przygotowanie do odbioru'],
        ];

        // Wstaw materiały
        foreach ($materials as $material) {
            Assortment::create([
                'type' => 'material',
                'name' => $material['name'],
                'category' => $material['category'],
                'unit' => $material['unit'],
                'default_price' => $material['default_price'],
                'description' => $material['description'] ?? null,
                'is_active' => true,
            ]);
        }
        
        echo "   ✓ Utworzono " . count($materials) . " materiałów\n";

        // Wstaw usługi
        foreach ($services as $service) {
            Assortment::create([
                'type' => 'service',
                'name' => $service['name'],
                'category' => $service['category'],
                'unit' => $service['unit'],
                'default_price' => $service['default_price'],
                'description' => $service['description'] ?? null,
                'is_active' => true,
            ]);
        }
        
        echo "   ✓ Utworzono " . count($services) . " usług\n";
    }
}
EOF

echo "✅ AssortmentSeeder zaktualizowany"

# =============================================================================
# 4. WorkstationSeeder - więcej stanowisk
# =============================================================================
echo "📝 Aktualizuję WorkstationSeeder..."

cat > database/seeders/WorkstationSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workstation;
use App\Models\User;

class WorkstationSeeder extends Seeder
{
    public function run(): void
    {
        $workstations = [
            // Lasery
            ['name' => 'LASER-01', 'type' => 'laser', 'location' => 'Hala A - Sekcja 1', 'status' => 'idle'],
            ['name' => 'LASER-02', 'type' => 'laser', 'location' => 'Hala A - Sekcja 1', 'status' => 'idle'],
            ['name' => 'LASER-03 (Fiber)', 'type' => 'laser', 'location' => 'Hala A - Sekcja 2', 'status' => 'idle'],
            ['name' => 'LASER-GRAW', 'type' => 'laser', 'location' => 'Hala A - Sekcja 2', 'status' => 'idle'],
            
            // CNC
            ['name' => 'CNC-01 (Duży)', 'type' => 'cnc', 'location' => 'Hala B - Sekcja 1', 'status' => 'idle'],
            ['name' => 'CNC-02 (Średni)', 'type' => 'cnc', 'location' => 'Hala B - Sekcja 1', 'status' => 'idle'],
            ['name' => 'CNC-03 (Mały)', 'type' => 'cnc', 'location' => 'Hala B - Sekcja 2', 'status' => 'idle'],
            ['name' => 'CNC-NEST', 'type' => 'cnc', 'location' => 'Hala B - Sekcja 2', 'status' => 'idle'],
            
            // Montaż
            ['name' => 'MONTAŻ-01', 'type' => 'assembly', 'location' => 'Hala C - Linia 1', 'status' => 'idle'],
            ['name' => 'MONTAŻ-02', 'type' => 'assembly', 'location' => 'Hala C - Linia 1', 'status' => 'idle'],
            ['name' => 'MONTAŻ-03', 'type' => 'assembly', 'location' => 'Hala C - Linia 2', 'status' => 'idle'],
            ['name' => 'MONTAŻ-ELEKTRO', 'type' => 'assembly', 'location' => 'Hala C - Linia 2', 'status' => 'idle'],
            
            // Malarnia
            ['name' => 'KABINA-01', 'type' => 'painting', 'location' => 'Hala D - Malarnia', 'status' => 'idle'],
            ['name' => 'KABINA-02', 'type' => 'painting', 'location' => 'Hala D - Malarnia', 'status' => 'idle'],
            ['name' => 'SUSZARNIA', 'type' => 'painting', 'location' => 'Hala D - Malarnia', 'status' => 'idle'],
            
            // Inne
            ['name' => 'SZLIFIERNIA', 'type' => 'other', 'location' => 'Hala E - Obróbka', 'status' => 'idle'],
            ['name' => 'WIERTARKA-KOLUMNOWA', 'type' => 'other', 'location' => 'Hala E - Obróbka', 'status' => 'idle'],
            ['name' => 'GIĘTARKA-PLEKSI', 'type' => 'other', 'location' => 'Hala E - Obróbka', 'status' => 'idle'],
            ['name' => 'PAKOWANIE-01', 'type' => 'other', 'location' => 'Hala F - Pakowanie', 'status' => 'idle'],
            ['name' => 'PAKOWANIE-02', 'type' => 'other', 'location' => 'Hala F - Pakowanie', 'status' => 'idle'],
        ];

        foreach ($workstations as $ws) {
            Workstation::create($ws);
        }
        
        echo "   ✓ Utworzono " . count($workstations) . " stanowisk\n";

        // Przypisz operatorów do stanowisk
        $this->assignOperators();
    }

    private function assignOperators(): void
    {
        // Pobierz pracowników
        $workers = User::where('role', 'worker')->get();
        
        if ($workers->isEmpty()) {
            echo "   ⚠ Brak pracowników - pominięto przypisanie operatorów\n";
            return;
        }

        // Lasery
        $laser1 = Workstation::where('name', 'LASER-01')->first();
        $laser2 = Workstation::where('name', 'LASER-02')->first();
        
        if ($laser1 && $workers->count() >= 3) {
            $laser1->operators()->attach($workers[0]->id, ['is_primary' => true]);
            $laser1->operators()->attach($workers[1]->id, ['is_primary' => false]);
        }
        
        if ($laser2 && $workers->count() >= 3) {
            $laser2->operators()->attach($workers[1]->id, ['is_primary' => true]);
            $laser2->operators()->attach($workers[2]->id, ['is_primary' => false]);
        }

        // CNC
        $cnc1 = Workstation::where('name', 'CNC-01 (Duży)')->first();
        $cnc2 = Workstation::where('name', 'CNC-02 (Średni)')->first();
        
        if ($cnc1 && $workers->count() >= 6) {
            $cnc1->operators()->attach($workers[3]->id, ['is_primary' => true]);
            $cnc1->operators()->attach($workers[4]->id, ['is_primary' => false]);
        }
        
        if ($cnc2 && $workers->count() >= 6) {
            $cnc2->operators()->attach($workers[4]->id, ['is_primary' => true]);
            $cnc2->operators()->attach($workers[5]->id, ['is_primary' => false]);
        }

        // Montaż
        $montaz1 = Workstation::where('name', 'MONTAŻ-01')->first();
        $montaz2 = Workstation::where('name', 'MONTAŻ-02')->first();
        
        if ($montaz1 && $workers->count() >= 9) {
            $montaz1->operators()->attach($workers[6]->id, ['is_primary' => true]);
            $montaz1->operators()->attach($workers[7]->id, ['is_primary' => false]);
        }
        
        if ($montaz2 && $workers->count() >= 9) {
            $montaz2->operators()->attach($workers[7]->id, ['is_primary' => true]);
            $montaz2->operators()->attach($workers[8]->id, ['is_primary' => false]);
        }

        // Malarnia
        $kabina1 = Workstation::where('name', 'KABINA-01')->first();
        
        if ($kabina1 && $workers->count() >= 11) {
            $kabina1->operators()->attach($workers[9]->id, ['is_primary' => true]);
            $kabina1->operators()->attach($workers[10]->id, ['is_primary' => false]);
        }

        echo "   ✓ Przypisano operatorów do stanowisk\n";
    }
}
EOF

echo "✅ WorkstationSeeder zaktualizowany"

# =============================================================================
# 5. Główny Seeder Produkcyjny (nowy kompletny)
# =============================================================================
echo "📝 Tworzę kompletny ProductionSeeder..."

cat > database/seeders/ProductionSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemMaterial;
use App\Models\QuotationItemService;
use App\Models\Prototype;
use App\Models\ProductionOrder;
use App\Models\ProductionService;
use App\Models\ProductionMaterial;
use App\Models\ServiceTimeLog;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Assortment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Workstation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    private $materials;
    private $services;
    private $customers;
    private $users;
    private $workstations;

    public function run(): void
    {
        echo "🏭 Rozpoczynam tworzenie rozbudowanych danych produkcyjnych...\n\n";

        // Załaduj dane pomocnicze
        $this->materials = Assortment::where('type', 'material')->get();
        $this->services = Assortment::where('type', 'service')->get();
        $this->customers = Customer::all();
        $this->users = User::all();
        $this->workstations = Workstation::all();

        if ($this->customers->isEmpty() || $this->materials->isEmpty() || $this->services->isEmpty()) {
            echo "⚠️  Brak wymaganych danych - uruchom najpierw inne seedery\n";
            return;
        }

        DB::beginTransaction();
        
        try {
            // Tworzenie zamówień w różnych fazach
            $this->createBriefOrders();           // Faza: brief
            $this->createQuotationOrders();       // Faza: quotation
            $this->createPrototypeOrders();       // Faza: prototype
            $this->createProductionOrders();      // Faza: production
            $this->createDeliveryOrders();        // Faza: delivery
            $this->createCompletedOrders();       // Faza: completed
            $this->createCancelledOrders();       // Faza: cancelled

            DB::commit();
            
            echo "\n✅ ZAKOŃCZONE!\n";
            echo "📋 Podsumowanie:\n";
            echo "   ✓ Zamówienia: " . Order::count() . "\n";
            echo "   ✓ Linie produktowe: " . ProductLine::count() . "\n";
            echo "   ✓ Wyceny: " . Quotation::count() . "\n";
            echo "   ✓ Prototypy: " . Prototype::count() . "\n";
            echo "   ✓ Zlecenia produkcyjne: " . ProductionOrder::count() . "\n";
            echo "   ✓ Zadania produkcyjne: " . ProductionService::count() . "\n";
            echo "   ✓ Dostawy: " . Delivery::count() . "\n";
            echo "   ✓ Faktury: " . Invoice::count() . "\n";
            echo "   ✓ Płatności: " . Payment::count() . "\n";
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Błąd: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Zamówienia w fazie BRIEF (nowe, bez wycen)
     */
    private function createBriefOrders(): void
    {
        echo "📝 Tworzę zamówienia w fazie BRIEF...\n";

        $briefs = [
            [
                'brief' => 'Potrzebujemy 50 stojaków ekspozycyjnych na produkty kosmetyczne. Wysokość ok. 160cm, 4 półki. Kolor: biały mat. Logo klienta grawerowane na górze.',
                'budget' => 35000,
                'lines' => [
                    ['name' => 'Stojak kosmetyczny 160cm biały', 'qty' => 50],
                ]
            ],
            [
                'brief' => 'Zabudowa stoiska targowego 4x3m na targi ITM Poznań. Ścianka tylna z grafiką, lada, 2 stoliki wysokie. Nowoczesny design, dominujący kolor niebieski.',
                'budget' => 28000,
                'lines' => [
                    ['name' => 'Ścianka tylna 4m z grafiką', 'qty' => 1],
                    ['name' => 'Lada recepcyjna', 'qty' => 1],
                    ['name' => 'Stolik wysoki fi60', 'qty' => 2],
                ]
            ],
            [
                'brief' => 'Meble do showroomu samochodowego - 3 biurka dla handlowców, szafka na dokumenty, stolik kawowy do strefy klienta. Wykończenie: fornir dębowy + metal czarny.',
                'budget' => 22000,
                'lines' => [
                    ['name' => 'Biurko handlowca 140x70', 'qty' => 3],
                    ['name' => 'Szafka dokumentowa', 'qty' => 1],
                    ['name' => 'Stolik kawowy 120x60', 'qty' => 1],
                ]
            ],
        ];

        foreach ($briefs as $index => $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'brief',
                'payment_status' => 'unpaid',
                'created_at' => Carbon::now()->subDays(rand(1, 5)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex), // A, B, C...
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'quotation',
                ]);
            }
        }

        echo "   ✓ Utworzono " . count($briefs) . " zamówień brief\n";
    }

    /**
     * Zamówienia w fazie QUOTATION (z wycenami)
     */
    private function createQuotationOrders(): void
    {
        echo "📝 Tworzę zamówienia w fazie QUOTATION...\n";

        $orders = [
            [
                'brief' => 'Roll-upy reklamowe 100x200cm dla sieci fitness - 80 sztuk. Konstrukcja aluminiowa, grafika full color. Walizki transportowe.',
                'budget' => 24000,
                'lines' => [
                    ['name' => 'Roll-up Premium 100x200', 'qty' => 80, 'materials_cost' => 8000, 'services_cost' => 4000],
                ]
            ],
            [
                'brief' => 'Regały ekspozycyjne do sklepu z elektroniką. 10 regałów ściennych 200x80cm, 5 gondoli wolnostojących. Oświetlenie LED, kolor grafit.',
                'budget' => 65000,
                'lines' => [
                    ['name' => 'Regał ścienny 200x80 LED', 'qty' => 10, 'materials_cost' => 18000, 'services_cost' => 12000],
                    ['name' => 'Gondola wolnostojąca', 'qty' => 5, 'materials_cost' => 12000, 'services_cost' => 8000],
                ]
            ],
            [
                'brief' => 'Potykacze reklamowe A1 - 30 sztuk, dwustronne, z kieszeniami na ulotki. Konstrukcja aluminiowa srebrna.',
                'budget' => 9000,
                'lines' => [
                    ['name' => 'Potykacz A1 dwustronny', 'qty' => 30, 'materials_cost' => 4500, 'services_cost' => 2000],
                ]
            ],
            [
                'brief' => 'Wyposażenie biura - 12 biurek narożnych, 12 kontenerów, 24 krzesła. Kolory: biały/dąb sonoma.',
                'budget' => 48000,
                'lines' => [
                    ['name' => 'Biurko narożne 160x120', 'qty' => 12, 'materials_cost' => 14400, 'services_cost' => 7200],
                    ['name' => 'Kontener 3-szufladowy', 'qty' => 12, 'materials_cost' => 6000, 'services_cost' => 3600],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;
        
        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'quotation',
                'payment_status' => 'unpaid',
                'created_at' => Carbon::now()->subDays(rand(6, 15)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                $line = ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'quotation',
                ]);

                // Utwórz wycenę
                $this->createQuotation($line, $lineData['materials_cost'], $lineData['services_cost']);
            }
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień quotation\n";
    }

    /**
     * Zamówienia w fazie PROTOTYPE (z zatwierdzonymi wycenami i prototypami)
     */
    private function createPrototypeOrders(): void
    {
        echo "📝 Tworzę zamówienia w fazie PROTOTYPE...\n";

        $orders = [
            [
                'brief' => 'Kasety świetlne LED 120x80cm na elewację budynku - 15 sztuk. Dwustronne, druk solwentowy, podświetlenie edge-lit.',
                'budget' => 45000,
                'lines' => [
                    ['name' => 'Kaseta świetlna 120x80 LED', 'qty' => 15, 'materials_cost' => 22500, 'services_cost' => 12000],
                ]
            ],
            [
                'brief' => 'Standy produktowe do perfumerii - 25 sztuk. Pleksi bezbarwna, grawerowanie, podświetlenie LED z dołu.',
                'budget' => 18000,
                'lines' => [
                    ['name' => 'Stand perfumeryjny pleksi LED', 'qty' => 25, 'materials_cost' => 8750, 'services_cost' => 5000],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;

        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'prototype',
                'payment_status' => 'unpaid',
                'created_at' => Carbon::now()->subDays(rand(16, 25)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                $line = ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'prototype',
                ]);

                // Wycena zatwierdzona
                $quotation = $this->createQuotation($line, $lineData['materials_cost'], $lineData['services_cost'], true);

                // Prototyp w trakcie
                Prototype::create([
                    'product_line_id' => $line->id,
                    'version_number' => 1,
                    'is_approved' => false,
                    'test_result' => 'pending',
                    'feedback_notes' => 'Prototyp w produkcji - oczekiwanie na testy klienta',
                    'sent_to_client_date' => Carbon::now()->subDays(rand(1, 5)),
                ]);
            }
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień prototype\n";
    }

    /**
     * Zamówienia w fazie PRODUCTION (w trakcie produkcji)
     */
    private function createProductionOrders(): void
    {
        echo "📝 Tworzę zamówienia w fazie PRODUCTION...\n";

        $orders = [
            [
                'brief' => 'Displaye na okulary do sieci optycznej - 40 sztuk. Obrotowe, 24 pary okularów na każdy. MDF lakierowany biały.',
                'budget' => 56000,
                'lines' => [
                    ['name' => 'Display obrotowy 24 pary', 'qty' => 40, 'materials_cost' => 28000, 'services_cost' => 16000, 'progress' => 60],
                ]
            ],
            [
                'brief' => 'Meble do restauracji - 15 stolików 80x80, 60 krzeseł, lada barowa 4m. Drewno dębowe olejowane, stelaże metalowe czarne.',
                'budget' => 95000,
                'lines' => [
                    ['name' => 'Stolik restauracyjny 80x80', 'qty' => 15, 'materials_cost' => 18000, 'services_cost' => 9000, 'progress' => 80],
                    ['name' => 'Krzesło drewno/metal', 'qty' => 60, 'materials_cost' => 30000, 'services_cost' => 18000, 'progress' => 40],
                    ['name' => 'Lada barowa 4m', 'qty' => 1, 'materials_cost' => 8000, 'services_cost' => 5000, 'progress' => 20],
                ]
            ],
            [
                'brief' => 'Standy ekspozycyjne do sklepu AGD - 8 dużych standów na pralki/lodówki. Konstrukcja stalowa, panele z dibondu.',
                'budget' => 32000,
                'lines' => [
                    ['name' => 'Stand AGD duży 180x100', 'qty' => 8, 'materials_cost' => 16000, 'services_cost' => 10000, 'progress' => 50],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;

        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'production',
                'payment_status' => 'partial',
                'created_at' => Carbon::now()->subDays(rand(26, 40)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                $line = ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'production',
                ]);

                // Wycena zatwierdzona
                $quotation = $this->createQuotation($line, $lineData['materials_cost'], $lineData['services_cost'], true);

                // Prototyp zatwierdzony
                $prototype = Prototype::create([
                    'product_line_id' => $line->id,
                    'version_number' => 1,
                    'is_approved' => true,
                    'test_result' => 'passed',
                    'feedback_notes' => 'Zatwierdzony bez uwag',
                    'sent_to_client_date' => Carbon::now()->subDays(rand(20, 30)),
                    'client_response_date' => Carbon::now()->subDays(rand(15, 19)),
                ]);

                $line->update(['approved_prototype_id' => $prototype->id]);

                // Zlecenie produkcyjne z zadaniami
                $this->createProductionOrder($line, $lineData['materials_cost'], $lineData['services_cost'], $lineData['progress']);
            }
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień production\n";
    }

    /**
     * Zamówienia w fazie DELIVERY (gotowe do wysyłki)
     */
    private function createDeliveryOrders(): void
    {
        echo "📝 Tworzę zamówienia w fazie DELIVERY...\n";

        $orders = [
            [
                'brief' => 'Lady sklepowe do sieci odzieżowej - 20 sztuk. MDF biały połysk, blaty szklane, oświetlenie LED.',
                'budget' => 78000,
                'lines' => [
                    ['name' => 'Lada sklepowa 150x60 LED', 'qty' => 20, 'materials_cost' => 40000, 'services_cost' => 24000],
                ]
            ],
            [
                'brief' => 'Totemy reklamowe zewnętrzne 250cm - 6 sztuk. Konstrukcja aluminiowa, kasety świetlne, fundamenty.',
                'budget' => 54000,
                'lines' => [
                    ['name' => 'Totem zewnętrzny 250cm', 'qty' => 6, 'materials_cost' => 30000, 'services_cost' => 18000],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;

        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'delivery',
                'payment_status' => 'partial',
                'created_at' => Carbon::now()->subDays(rand(41, 55)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                $line = ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'delivery',
                ]);

                // Wycena zatwierdzona
                $this->createQuotation($line, $lineData['materials_cost'], $lineData['services_cost'], true);

                // Prototyp zatwierdzony
                $prototype = Prototype::create([
                    'product_line_id' => $line->id,
                    'version_number' => 1,
                    'is_approved' => true,
                    'test_result' => 'passed',
                ]);
                $line->update(['approved_prototype_id' => $prototype->id]);

                // Zlecenie produkcyjne zakończone
                $this->createProductionOrder($line, $lineData['materials_cost'], $lineData['services_cost'], 100);

                // Dostawa zaplanowana
                $this->createDelivery($line);
            }

            // Faktura
            $this->createInvoice($order, 'issued');
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień delivery\n";
    }

    /**
     * Zamówienia COMPLETED (zakończone)
     */
    private function createCompletedOrders(): void
    {
        echo "📝 Tworzę zamówienia COMPLETED...\n";

        $orders = [
            [
                'brief' => 'Wyposażenie apteki - lady, regały, witryny. Realizacja kompleksowa.',
                'budget' => 120000,
                'lines' => [
                    ['name' => 'Lada apteczna główna', 'qty' => 1, 'materials_cost' => 15000, 'services_cost' => 8000],
                    ['name' => 'Regał apteczny ścienny', 'qty' => 12, 'materials_cost' => 36000, 'services_cost' => 18000],
                    ['name' => 'Witryna szklana chłodzona', 'qty' => 2, 'materials_cost' => 16000, 'services_cost' => 10000],
                ]
            ],
            [
                'brief' => 'Meble biurowe dla kancelarii prawnej - 8 biurek, szafy na akta, stoły konferencyjne.',
                'budget' => 68000,
                'lines' => [
                    ['name' => 'Biurko gabinetowe 180x80', 'qty' => 8, 'materials_cost' => 24000, 'services_cost' => 12000],
                    ['name' => 'Szafa aktowa 200x100', 'qty' => 6, 'materials_cost' => 18000, 'services_cost' => 9000],
                ]
            ],
            [
                'brief' => 'Standy promocyjne na napoje - rollup + display podłogowy dla sieci sklepów.',
                'budget' => 15000,
                'lines' => [
                    ['name' => 'Zestaw promocyjny napoje', 'qty' => 50, 'materials_cost' => 7500, 'services_cost' => 4500],
                ]
            ],
            [
                'brief' => 'Zabudowa stoiska na targi budowlane BUDMA. 6x4m, pełna zabudowa.',
                'budget' => 85000,
                'lines' => [
                    ['name' => 'Kompletna zabudowa stoiska 6x4m', 'qty' => 1, 'materials_cost' => 45000, 'services_cost' => 28000],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;

        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subDays(rand(56, 90)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                $line = ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'completed',
                ]);

                // Wycena zatwierdzona
                $this->createQuotation($line, $lineData['materials_cost'], $lineData['services_cost'], true);

                // Prototyp zatwierdzony
                $prototype = Prototype::create([
                    'product_line_id' => $line->id,
                    'version_number' => 1,
                    'is_approved' => true,
                    'test_result' => 'passed',
                ]);
                $line->update(['approved_prototype_id' => $prototype->id]);

                // Zlecenie produkcyjne zakończone
                $this->createProductionOrder($line, $lineData['materials_cost'], $lineData['services_cost'], 100);

                // Dostawa zrealizowana
                $this->createDelivery($line, true);
            }

            // Faktura opłacona
            $invoice = $this->createInvoice($order, 'paid');
            $this->createPayment($invoice);
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień completed\n";
    }

    /**
     * Zamówienia CANCELLED (anulowane)
     */
    private function createCancelledOrders(): void
    {
        echo "📝 Tworzę zamówienia CANCELLED...\n";

        $orders = [
            [
                'brief' => 'Meble hotelowe - projekt anulowany przez klienta z powodu zmiany inwestora.',
                'budget' => 180000,
                'lines' => [
                    ['name' => 'Łóżko hotelowe 180', 'qty' => 50],
                    ['name' => 'Szafka nocna', 'qty' => 100],
                ]
            ],
            [
                'brief' => 'Stoisko eventowe - wydarzenie odwołane.',
                'budget' => 25000,
                'lines' => [
                    ['name' => 'Zabudowa eventowa', 'qty' => 1],
                ]
            ],
        ];

        $orderNum = Order::count() + 1;

        foreach ($orders as $data) {
            $order = Order::create([
                'customer_id' => $this->customers->random()->id,
                'order_number' => 'ZAM/' . date('Y') . '/' . str_pad($orderNum++, 3, '0', STR_PAD_LEFT),
                'brief' => $data['brief'],
                'budget_max' => $data['budget'],
                'overall_status' => 'cancelled',
                'payment_status' => 'unpaid',
                'created_at' => Carbon::now()->subDays(rand(30, 60)),
            ]);

            foreach ($data['lines'] as $lineIndex => $lineData) {
                ProductLine::create([
                    'order_id' => $order->id,
                    'line_number' => chr(65 + $lineIndex),
                    'name' => $lineData['name'],
                    'quantity' => $lineData['qty'],
                    'status' => 'cancelled',
                ]);
            }
        }

        echo "   ✓ Utworzono " . count($orders) . " zamówień cancelled\n";
    }

    // =========================================================================
    // METODY POMOCNICZE
    // =========================================================================

    private function createQuotation(ProductLine $line, float $materialsCost, float $servicesCost, bool $approved = false): Quotation
    {
        $subtotal = $materialsCost + $servicesCost;
        $marginPercent = rand(20, 35);
        $margin = $subtotal * ($marginPercent / 100);
        $totalNet = $subtotal + $margin;
        $totalGross = $totalNet * 1.23;

        $quotation = Quotation::create([
            'product_line_id' => $line->id,
            'version_number' => 1,
            'total_materials_cost' => $materialsCost,
            'total_services_cost' => $servicesCost,
            'total_net' => $totalNet,
            'total_gross' => $totalGross,
            'margin_percent' => $marginPercent,
            'is_approved' => $approved,
            'approved_at' => $approved ? Carbon::now()->subDays(rand(5, 15)) : null,
            'approved_by_user_id' => $approved ? $this->users->where('role', 'manager')->first()?->id : null,
        ]);

        // Pozycja wyceny
        $item = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'materials_cost' => $materialsCost,
            'services_cost' => $servicesCost,
            'subtotal' => $subtotal,
        ]);

        // Materiały
        $remainingMaterialsCost = $materialsCost;
        $materialsToAdd = $this->materials->random(min(5, $this->materials->count()));
        
        foreach ($materialsToAdd as $index => $material) {
            $isLast = ($index === count($materialsToAdd) - 1);
            $cost = $isLast ? $remainingMaterialsCost : rand(100, (int)($remainingMaterialsCost / 2));
            $remainingMaterialsCost -= $cost;
            
            if ($cost <= 0) continue;
            
            $qty = max(1, round($cost / $material->default_price, 2));
            
            QuotationItemMaterial::create([
                'quotation_item_id' => $item->id,
                'assortment_item_id' => $material->id,
                'quantity' => $qty,
                'unit' => $material->unit,
                'unit_price' => $material->default_price,
                'total_cost' => $cost,
            ]);
        }

        // Usługi
        $remainingServicesCost = $servicesCost;
        $servicesToAdd = $this->services->random(min(4, $this->services->count()));
        
        foreach ($servicesToAdd as $index => $service) {
            $isLast = ($index === count($servicesToAdd) - 1);
            $cost = $isLast ? $remainingServicesCost : rand(50, (int)($remainingServicesCost / 2));
            $remainingServicesCost -= $cost;
            
            if ($cost <= 0) continue;
            
            $hours = max(0.5, round($cost / $service->default_price, 2));
            
            QuotationItemService::create([
                'quotation_item_id' => $item->id,
                'assortment_item_id' => $service->id,
                'estimated_quantity' => 1,
                'estimated_time_hours' => $hours,
                'unit' => 'h',
                'unit_price' => $service->default_price,
                'total_cost' => $cost,
            ]);
        }

        return $quotation;
    }

    private function createProductionOrder(ProductLine $line, float $estimatedMaterialsCost, float $estimatedServicesCost, int $progressPercent): ProductionOrder
    {
        $status = $progressPercent >= 100 ? 'completed' : ($progressPercent > 0 ? 'in_progress' : 'planned');
        
        $productionOrder = ProductionOrder::create([
            'product_line_id' => $line->id,
            'quantity' => $line->quantity,
            'total_estimated_cost' => $estimatedMaterialsCost + $estimatedServicesCost,
            'total_actual_cost' => $status === 'completed' ? ($estimatedMaterialsCost + $estimatedServicesCost) * (rand(90, 110) / 100) : null,
            'status' => $status,
            'started_at' => $progressPercent > 0 ? Carbon::now()->subDays(rand(5, 20)) : null,
            'completed_at' => $status === 'completed' ? Carbon::now()->subDays(rand(1, 5)) : null,
        ]);

        // Zadania produkcyjne
        $tasks = [
            ['name' => 'Cięcie laserowe', 'type' => 'laser', 'hours' => rand(2, 8)],
            ['name' => 'Frezowanie CNC', 'type' => 'cnc', 'hours' => rand(3, 10)],
            ['name' => 'Szlifowanie', 'type' => 'other', 'hours' => rand(1, 4)],
            ['name' => 'Malowanie', 'type' => 'painting', 'hours' => rand(2, 6)],
            ['name' => 'Montaż', 'type' => 'assembly', 'hours' => rand(4, 12)],
            ['name' => 'Pakowanie', 'type' => 'other', 'hours' => rand(1, 3)],
        ];

        $completedTasks = (int)ceil(count($tasks) * ($progressPercent / 100));

        foreach ($tasks as $index => $task) {
            $workstation = $this->workstations->where('type', $task['type'])->first() 
                ?? $this->workstations->first();
            
            $worker = $this->users->where('role', 'worker')->random();
            $isCompleted = $index < $completedTasks;
            $isInProgress = $index === $completedTasks && $progressPercent < 100;
            
            $taskStatus = 'planned';
            if ($isCompleted) $taskStatus = 'completed';
            elseif ($isInProgress) $taskStatus = 'in_progress';

            $estimatedHours = $task['hours'];
            $unitPrice = $this->services->random()->default_price;

            $productionService = ProductionService::create([
                'production_order_id' => $productionOrder->id,
                'step_number' => $index + 1,
                'service_name' => $task['name'],
                'workstation_id' => $workstation->id,
                'assigned_to_user_id' => $taskStatus !== 'planned' ? $worker->id : null,
                'estimated_quantity' => $line->quantity,
                'estimated_time_hours' => $estimatedHours,
                'unit_price' => $unitPrice,
                'estimated_cost' => $estimatedHours * $unitPrice,
                'actual_quantity' => $isCompleted ? $line->quantity : null,
                'actual_time_hours' => $isCompleted ? $estimatedHours * (rand(85, 115) / 100) : null,
                'actual_cost' => $isCompleted ? $estimatedHours * $unitPrice * (rand(85, 115) / 100) : null,
                'status' => $taskStatus,
                'actual_start_date' => $taskStatus !== 'planned' ? Carbon::now()->subDays(rand(1, 10)) : null,
                'actual_end_date' => $isCompleted ? Carbon::now()->subDays(rand(0, 5)) : null,
            ]);

            // Logi czasu dla zakończonych zadań
            if ($isCompleted && $worker) {
                ServiceTimeLog::create([
                    'production_service_id' => $productionService->id,
                    'user_id' => $worker->id,
                    'event_type' => 'start',
                    'event_timestamp' => Carbon::now()->subDays(rand(5, 10)),
                    'elapsed_seconds' => 0,
                ]);
                
                ServiceTimeLog::create([
                    'production_service_id' => $productionService->id,
                    'user_id' => $worker->id,
                    'event_type' => 'stop',
                    'event_timestamp' => Carbon::now()->subDays(rand(1, 4)),
                    'elapsed_seconds' => (int)($productionService->actual_time_hours * 3600),
                ]);
            }
        }

        // Materiały produkcyjne
        $materialsUsed = $this->materials->random(min(3, $this->materials->count()));
        foreach ($materialsUsed as $material) {
            $qty = rand(5, 50);
            ProductionMaterial::create([
                'production_order_id' => $productionOrder->id,
                'assortment_item_id' => $material->id,
                'planned_quantity' => $qty,
                'actual_quantity' => $status === 'completed' ? $qty * (rand(95, 105) / 100) : null,
                'unit' => $material->unit,
                'unit_price' => $material->default_price,
                'total_cost' => $qty * $material->default_price,
            ]);
        }

        return $productionOrder;
    }

    private function createDelivery(ProductLine $line, bool $delivered = false): Delivery
    {
        $deliveryNum = Delivery::count() + 1;
        
        return Delivery::create([
            'product_line_id' => $line->id,
            'delivery_number' => 'DOS/' . date('Y') . '/' . str_pad($deliveryNum, 4, '0', STR_PAD_LEFT),
            'delivery_date' => $delivered ? Carbon::now()->subDays(rand(1, 10)) : Carbon::now()->addDays(rand(1, 7)),
            'tracking_number' => $delivered ? 'PL' . rand(100000000, 999999999) . 'PL' : null,
            'courier' => ['DPD', 'DHL', 'UPS', 'Własny transport'][rand(0, 3)],
            'status' => $delivered ? 'delivered' : 'scheduled',
            'delivered_at' => $delivered ? Carbon::now()->subDays(rand(1, 5)) : null,
            'notes' => $delivered ? 'Dostarczone bez uwag' : 'Wymagane awizowanie',
        ]);
    }

    private function createInvoice(Order $order, string $status): Invoice
    {
        $invoiceNum = Invoice::count() + 1;
        
        // Oblicz sumę wycen
        $totalNet = 0;
        foreach ($order->productLines as $line) {
            $quotation = $line->quotations()->where('is_approved', true)->first();
            if ($quotation) {
                $totalNet += $quotation->total_net;
            }
        }
        
        if ($totalNet === 0) {
            $totalNet = $order->budget_max * 0.8; // Fallback
        }

        $totalGross = $totalNet * 1.23;

        return Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'FV/' . date('Y') . '/' . str_pad($invoiceNum, 4, '0', STR_PAD_LEFT),
            'total_net' => $totalNet,
            'total_gross' => $totalGross,
            'issue_date' => Carbon::now()->subDays(rand(5, 20)),
            'payment_deadline' => Carbon::now()->addDays(rand(-5, 14)),
            'status' => $status,
            'paid_at' => $status === 'paid' ? Carbon::now()->subDays(rand(1, 10)) : null,
        ]);
    }

    private function createPayment(Invoice $invoice): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total_gross,
            'payment_date' => $invoice->paid_at ?? Carbon::now(),
            'payment_method' => ['transfer', 'card', 'cash'][rand(0, 2)],
            'transaction_id' => 'TR' . rand(100000, 999999),
            'notes' => 'Płatność otrzymana',
        ]);
    }
}
EOF

echo "✅ ProductionSeeder zaktualizowany"

# =============================================================================
# 6. Aktualizacja DatabaseSeeder
# =============================================================================
echo "📝 Aktualizuję DatabaseSeeder..."

cat > database/seeders/DatabaseSeeder.php << 'EOF'
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
EOF

echo "✅ DatabaseSeeder zaktualizowany"

# =============================================================================
# 7. Uruchomienie migracji i seederów
# =============================================================================
echo ""
echo "🔄 Uruchamiam migrate:fresh --seed..."

php artisan migrate:fresh --seed

echo ""
echo "✅ ZAKOŃCZONE!"
echo ""
echo "📋 Co zostało zrobione:"
echo "   ✓ UserSeeder - 18 użytkowników (admin, manager, worker)"
echo "   ✓ CustomerSeeder - 20 klientów (B2B i B2C)"
echo "   ✓ AssortmentSeeder - ~120 materiałów i ~50 usług"
echo "   ✓ WorkstationSeeder - 20 stanowisk z operatorami"
echo "   ✓ ProductionSeeder - zamówienia we wszystkich fazach:"
echo "      • brief - 3 zamówienia"
echo "      • quotation - 4 zamówienia"
echo "      • prototype - 2 zamówienia"
echo "      • production - 3 zamówienia"
echo "      • delivery - 2 zamówienia"
echo "      • completed - 4 zamówienia"
echo "      • cancelled - 2 zamówienia"
echo ""
echo "🧪 Testuj:"
echo "   php artisan tinker"
echo "   >>> Order::with('productLines')->get()->count()"
echo "   >>> ProductLine::count()"
echo "   >>> Quotation::where('is_approved', true)->count()"