<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        echo "👥 Tworzenie użytkowników...\n";

        // =====================================================================
        // ADMINISTRACJA I ZARZĄDZANIE
        // =====================================================================
        $management = [
            ['name' => 'Administrator Systemu', 'email' => 'admin@cserp.pl', 'role' => UserRole::ADMIN, 'pin' => '9999'],
            ['name' => 'Tomasz Kierownik', 'email' => 'tomasz.pm@cserp.pl', 'role' => UserRole::PROJECT_MANAGER],
            ['name' => 'Monika Kierownik', 'email' => 'monika.pm@cserp.pl', 'role' => UserRole::PROJECT_MANAGER],
        ];

        // =====================================================================
        // HANDLOWCY
        // =====================================================================
        $traders = [
            ['name' => 'Marek Handlowiec', 'email' => 'marek.trader@cserp.pl', 'role' => UserRole::TRADER],
            ['name' => 'Katarzyna Sprzedaż', 'email' => 'katarzyna.sales@cserp.pl', 'role' => UserRole::TRADER],
            ['name' => 'Robert Klient', 'email' => 'robert.client@cserp.pl', 'role' => UserRole::TRADER],
        ];

        // =====================================================================
        // BIURO
        // =====================================================================
        $office = [
            ['name' => 'Anna Biuro', 'email' => 'anna.admin@cserp.pl', 'role' => UserRole::ADMINISTRATIVE_EMPLOYEE],
            ['name' => 'Ewa Księgowa', 'email' => 'ewa.accounting@cserp.pl', 'role' => UserRole::ADMINISTRATIVE_EMPLOYEE],
        ];

        // =====================================================================
        // LOGISTYKA
        // =====================================================================
        $logistics = [
            ['name' => 'Piotr Logistyk', 'email' => 'piotr.logistics@cserp.pl', 'role' => UserRole::LOGISTICS_SPECIALIST],
            ['name' => 'Michał Magazyn', 'email' => 'michal.warehouse@cserp.pl', 'role' => UserRole::LOGISTICS_SPECIALIST],
        ];

        // =====================================================================
        // PRACOWNICY PRODUKCJI - LASER/CNC
        // =====================================================================
        $laserCnc = [
            ['name' => 'Jan Laser', 'email' => 'jan.laser@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '1111'],
            ['name' => 'Piotr CNC', 'email' => 'piotr.cnc@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '2222'],
            ['name' => 'Karol Frezarka', 'email' => 'karol.mill@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '1234'],
            ['name' => 'Bartosz Ploter', 'email' => 'bartosz.plotter@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '5678'],
        ];

        // =====================================================================
        // PRACOWNICY PRODUKCJI - DRUKARNIA
        // =====================================================================
        $printing = [
            ['name' => 'Maciej Druk UV', 'email' => 'maciej.uv@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '3333'],
            ['name' => 'Tomasz Solwent', 'email' => 'tomasz.solvent@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '4567'],
            ['name' => 'Iwona Wykończenia', 'email' => 'iwona.finish@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '7890'],
        ];

        // =====================================================================
        // PRACOWNICY PRODUKCJI - MONTAŻ
        // =====================================================================
        $assembly = [
            ['name' => 'Krzysztof Montaż', 'email' => 'krzysztof.assembly@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '4444'],
            ['name' => 'Marcin Stolarz', 'email' => 'marcin.carpenter@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '2345'],
            ['name' => 'Damian Elektryk', 'email' => 'damian.electric@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '6789'],
            ['name' => 'Paweł Senior', 'email' => 'pawel.senior@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '3456'],
        ];

        // =====================================================================
        // PRACOWNICY PRODUKCJI - MALOWANIE
        // =====================================================================
        $painting = [
            ['name' => 'Agnieszka Malarnia', 'email' => 'agnieszka.paint@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '5555'],
            ['name' => 'Jacek Lakiernik', 'email' => 'jacek.lacquer@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '4321'],
        ];

        // =====================================================================
        // PRACOWNICY PRODUKCJI - INNE
        // =====================================================================
        $other = [
            ['name' => 'Łukasz Giętarka', 'email' => 'lukasz.bending@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '6666'],
            ['name' => 'Sebastian Pakowanie', 'email' => 'sebastian.pack@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '7777'],
            ['name' => 'Wojciech Pomocnik', 'email' => 'wojciech.helper@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '8888'],
            ['name' => 'Daniel Szlifierka', 'email' => 'daniel.grinder@cserp.pl', 'role' => UserRole::PRODUCTION_EMPLOYEE, 'pin' => '9012'],
        ];

        // Łączenie wszystkich użytkowników
        $allUsers = array_merge(
            $management,
            $traders,
            $office,
            $logistics,
            $laserCnc,
            $printing,
            $assembly,
            $painting,
            $other
        );

        foreach ($allUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('pa$$word'),
                    'pin_code' => isset($userData['pin']) ? Hash::make($userData['pin']) : null,
                    'role' => $userData['role'],
                    'is_active' => true,
                ]
            );
        }

        echo " ✓ Utworzono " . count($allUsers) . " użytkowników\n";
        echo "   - Zarządzanie: " . count($management) . "\n";
        echo "   - Handlowcy: " . count($traders) . "\n";
        echo "   - Biuro: " . count($office) . "\n";
        echo "   - Logistyka: " . count($logistics) . "\n";
        echo "   - Produkcja (Laser/CNC): " . count($laserCnc) . "\n";
        echo "   - Produkcja (Drukarnia): " . count($printing) . "\n";
        echo "   - Produkcja (Montaż): " . count($assembly) . "\n";
        echo "   - Produkcja (Malowanie): " . count($painting) . "\n";
        echo "   - Produkcja (Inne): " . count($other) . "\n";
    }
}
