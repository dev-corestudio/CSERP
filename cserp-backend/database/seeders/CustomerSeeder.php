<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Enums\CustomerType;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏢 Tworzenie klientów...\n";

        $customers = [
            // =====================================================================
            // DUŻE KORPORACJE B2B
            // =====================================================================
            [
                'name' => 'ACME Corporation Sp. z o.o.',
                'nip' => '1234567890',
                'email' => 'zamowienia@acme.pl',
                'phone' => '+48 22 123 45 67',
                'address' => 'ul. Przemysłowa 100, 02-232 Warszawa',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Tech Solutions International',
                'nip' => '9876543210',
                'email' => 'procurement@techsolutions.pl',
                'phone' => '+48 12 987 65 43',
                'address' => 'al. Technologiczna 50, 30-300 Kraków',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Global Retail Group S.A.',
                'nip' => '5551112223',
                'email' => 'zakupy@globalretail.pl',
                'phone' => '+48 61 555 11 22',
                'address' => 'ul. Handlowa 88, 60-600 Poznań',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'MediaPro Holdings',
                'nip' => '7778889990',
                'email' => 'produkcja@mediapro.pl',
                'phone' => '+48 71 777 88 99',
                'address' => 'pl. Medialny 15, 50-500 Wrocław',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Exhibition Masters Polska',
                'nip' => '3334445556',
                'email' => 'projekty@exhmasters.pl',
                'phone' => '+48 32 333 44 55',
                'address' => 'ul. Targowa 99, 40-400 Katowice',
                'type' => CustomerType::B2B,
            ],

            // =====================================================================
            // ŚREDNIE FIRMY B2B - RETAIL
            // =====================================================================
            [
                'name' => 'FashionStyle Boutique',
                'nip' => '1112223334',
                'email' => 'biuro@fashionstyle.pl',
                'phone' => '+48 58 111 22 33',
                'address' => 'ul. Modowa 25, 80-800 Gdańsk',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Kosmetyki Premium Sp. z o.o.',
                'nip' => '4445556667',
                'email' => 'zamowienia@kosmetyki-premium.pl',
                'phone' => '+48 81 444 55 66',
                'address' => 'ul. Piękna 33, 20-200 Lublin',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Elektro Market Plus',
                'nip' => '6667778889',
                'email' => 'zakupy@elektromarket.pl',
                'phone' => '+48 91 666 77 88',
                'address' => 'ul. Elektroniczna 12, 70-700 Szczecin',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Książki & Multimedia',
                'nip' => '9990001112',
                'email' => 'sklep@ksiazkimedia.pl',
                'phone' => '+48 42 999 00 11',
                'address' => 'ul. Księgarnia 44, 90-900 Łódź',
                'type' => CustomerType::B2B,
            ],

            // =====================================================================
            // ŚREDNIE FIRMY B2B - USŁUGI
            // =====================================================================
            [
                'name' => 'Design Studio Kreatywne',
                'nip' => '1231231234',
                'email' => 'projekty@designstudio.pl',
                'phone' => '+48 52 123 12 31',
                'address' => 'ul. Artystyczna 5, 85-850 Bydgoszcz',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Marketing Pro Agency',
                'nip' => '3213213214',
                'email' => 'info@marketingpro.pl',
                'phone' => '+48 89 321 32 13',
                'address' => 'ul. Reklamowa 8, 10-100 Olsztyn',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'EventMasters Organizacja Eventów',
                'nip' => '5555666677',
                'email' => 'eventy@eventmasters.pl',
                'phone' => '+48 41 555 66 67',
                'address' => 'ul. Eventowa 22, 25-250 Kielce',
                'type' => CustomerType::B2B,
            ],

            // =====================================================================
            // MAŁE FIRMY B2B - PRODUKCJA/RZEMIOSŁO
            // =====================================================================
            [
                'name' => 'Furniture Factory Meble',
                'nip' => '7778889990',
                'email' => 'produkcja@furniturefactory.pl',
                'phone' => '+48 43 777 88 89',
                'address' => 'ul. Stolarska 11, 26-260 Radom',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Zakład Stolarski "Dąb"',
                'nip' => '1010202030',
                'email' => 'kontakt@stolarski-dab.pl',
                'phone' => '+48 87 101 02 03',
                'address' => 'ul. Dębowa 7, 15-150 Białystok',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'MetalWorks Konstrukcje',
                'nip' => '4040505060',
                'email' => 'biuro@metalworks.pl',
                'phone' => '+48 34 404 05 06',
                'address' => 'ul. Hutnicza 99, 44-440 Gliwice',
                'type' => CustomerType::B2B,
            ],

            // =====================================================================
            // MAŁE FIRMY B2B - PROFESJONALIŚCI
            // =====================================================================
            [
                'name' => 'Biuro Architektoniczne ArcDesign',
                'nip' => '7070808090',
                'email' => 'projekty@arcdesign.pl',
                'phone' => '+48 18 707 08 09',
                'address' => 'ul. Projektowa 3, 38-380 Przemyśl',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Foto Studio Professional',
                'nip' => '1000200030',
                'email' => 'studio@fotopro.pl',
                'phone' => '+48 77 100 02 00',
                'address' => 'ul. Fotograficzna 14, 58-580 Jelenia Góra',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Kancelaria Prawna LexPro',
                'nip' => '2000300040',
                'email' => 'kancelaria@lexpro.pl',
                'phone' => '+48 94 200 03 00',
                'address' => 'ul. Prawnicza 5, 66-660 Zielona Góra',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Centrum Medyczne MediCare',
                'nip' => '3000400050',
                'email' => 'recepcja@medicare-cm.pl',
                'phone' => '+48 59 300 04 00',
                'address' => 'ul. Zdrowia 18, 76-760 Słupsk',
                'type' => CustomerType::B2B,
            ],
            [
                'name' => 'Szkoła Językowa PolyGlot',
                'nip' => '4000500060',
                'email' => 'szkola@polyglot.edu.pl',
                'phone' => '+48 63 400 05 00',
                'address' => 'ul. Edukacyjna 9, 64-640 Leszno',
                'type' => CustomerType::B2B,
            ],

            // =====================================================================
            // KLIENCI B2C - RÓŻNE PROFILE
            // =====================================================================
            [
                'name' => 'Jan Kowalski',
                'nip' => null,
                'email' => 'jan.kowalski.priv@gmail.com',
                'phone' => '+48 500 123 456',
                'address' => 'ul. Domowa 5/12, 00-001 Warszawa',
                'type' => CustomerType::B2C,
            ],
            [
                'name' => 'Anna Nowak',
                'nip' => null,
                'email' => 'anna.nowak.home@wp.pl',
                'phone' => '+48 501 234 567',
                'address' => 'ul. Prywatna 10, 30-030 Kraków',
                'type' => CustomerType::B2C,
            ],
            [
                'name' => 'Piotr Wiśniewski',
                'nip' => null,
                'email' => 'p.wisniewski@interia.pl',
                'phone' => '+48 502 345 678',
                'address' => 'ul. Rodzinna 22/5, 60-060 Poznań',
                'type' => CustomerType::B2C,
            ],
            [
                'name' => 'Maria Dąbrowska',
                'nip' => null,
                'email' => 'maria.dabrowska@o2.pl',
                'phone' => '+48 503 456 789',
                'address' => 'ul. Słoneczna 8, 50-050 Wrocław',
                'type' => CustomerType::B2C,
            ],
            [
                'name' => 'Tomasz Lewandowski',
                'nip' => null,
                'email' => 'tomasz.l.87@gmail.com',
                'phone' => '+48 504 567 890',
                'address' => 'ul. Parkowa 15/3, 80-080 Gdańsk',
                'type' => CustomerType::B2C,
            ],
            [
                'name' => 'Katarzyna Zielińska',
                'nip' => null,
                'email' => 'kasia.zielinska@yahoo.com',
                'phone' => '+48 505 678 901',
                'address' => 'ul. Kwiatowa 33, 90-090 Łódź',
                'type' => CustomerType::B2C,
            ],
        ];

        $b2bCount = 0;
        $b2cCount = 0;

        foreach ($customers as $customer) {
            Customer::create(array_merge($customer, ['is_active' => true]));

            if ($customer['type'] === CustomerType::B2B) {
                $b2bCount++;
            } else {
                $b2cCount++;
            }
        }

        echo " ✓ Utworzono " . count($customers) . " klientów\n";
        echo "   - B2B (firmy): $b2bCount\n";
        echo "   - B2C (klienci indywidualni): $b2cCount\n";
    }
}
