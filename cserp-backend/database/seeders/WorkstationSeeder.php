<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workstation;
use App\Models\User;
use App\Enums\WorkstationType;
use App\Enums\WorkstationStatus;
use App\Enums\UserRole;

class WorkstationSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏭 Tworzenie stanowisk roboczych...\n";

        $workstations = [
            // =====================================================================
            // LASERY
            // =====================================================================
            ['name' => 'LASER-01', 'type' => WorkstationType::LASER, 'location' => 'Hala A - Sekcja 1'],
            ['name' => 'LASER-02', 'type' => WorkstationType::LASER, 'location' => 'Hala A - Sekcja 1'],

            // =====================================================================
            // CNC / PLOTERY
            // =====================================================================
            ['name' => 'CNC-01', 'type' => WorkstationType::CNC, 'location' => 'Hala A - Sekcja 2'],
            ['name' => 'CNC-02', 'type' => WorkstationType::CNC, 'location' => 'Hala A - Sekcja 2'],
            ['name' => 'PLOTER-CNC-01', 'type' => WorkstationType::CNC, 'location' => 'Hala B - Sekcja 1'],
            ['name' => 'PLOTER-CNC-02', 'type' => WorkstationType::CNC, 'location' => 'Hala B - Sekcja 1'],
            ['name' => 'FREZARKA-01', 'type' => WorkstationType::CNC, 'location' => 'Hala A - Sekcja 3'],

            // =====================================================================
            // DRUKARNIA
            // =====================================================================
            ['name' => 'UV-FLORA', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Drukarnia'],
            ['name' => 'UV-ROLAND', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Drukarnia'],
            ['name' => 'SOLWENT-01', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Drukarnia'],
            ['name' => 'SOLWENT-02', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Drukarnia'],
            ['name' => 'LAMINATOR', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Wykończenia'],
            ['name' => 'PLOTER-TNĄCY', 'type' => WorkstationType::PRINTING, 'location' => 'Hala C - Wykończenia'],

            // =====================================================================
            // MONTAŻ
            // =====================================================================
            ['name' => 'MONTAŻ-01', 'type' => WorkstationType::ASSEMBLY, 'location' => 'Hala D - Linia 1'],
            ['name' => 'MONTAŻ-02', 'type' => WorkstationType::ASSEMBLY, 'location' => 'Hala D - Linia 1'],
            ['name' => 'MONTAŻ-03', 'type' => WorkstationType::ASSEMBLY, 'location' => 'Hala D - Linia 2'],
            ['name' => 'MONTAŻ-ELEKTRO', 'type' => WorkstationType::ASSEMBLY, 'location' => 'Hala D - Elektro'],
            ['name' => 'STÓŁ-STOLARSKI', 'type' => WorkstationType::ASSEMBLY, 'location' => 'Hala D - Stolarnia'],

            // =====================================================================
            // MALOWANIE / LAKIEROWANIE
            // =====================================================================
            ['name' => 'KABINA-LAKIERNICZA-01', 'type' => WorkstationType::PAINTING, 'location' => 'Hala E - Malarnia'],
            ['name' => 'KABINA-LAKIERNICZA-02', 'type' => WorkstationType::PAINTING, 'location' => 'Hala E - Malarnia'],
            ['name' => 'SUSZARNIA', 'type' => WorkstationType::PAINTING, 'location' => 'Hala E - Suszarnia'],

            // =====================================================================
            // PRODUKCJA OGÓLNA
            // =====================================================================
            ['name' => 'GIĘTARKA-METAL', 'type' => WorkstationType::PRODUCTION, 'location' => 'Hala F - Obróbka'],
            ['name' => 'TERMOFORMOWANIE-MAŁE', 'type' => WorkstationType::PRODUCTION, 'location' => 'Hala F - Termo'],
            ['name' => 'TERMOFORMOWANIE-DUŻE', 'type' => WorkstationType::PRODUCTION, 'location' => 'Hala F - Termo'],
            ['name' => 'WYKRAWARKA', 'type' => WorkstationType::PRODUCTION, 'location' => 'Hala F - Obróbka'],

            // =====================================================================
            // INNE
            // =====================================================================
            ['name' => 'SZLIFIERNIA', 'type' => WorkstationType::OTHER, 'location' => 'Hala G - Wykończenia'],
            ['name' => 'POLERKA', 'type' => WorkstationType::OTHER, 'location' => 'Hala G - Wykończenia'],
            ['name' => 'PAKOWANIE-01', 'type' => WorkstationType::OTHER, 'location' => 'Hala H - Magazyn'],
            ['name' => 'PAKOWANIE-02', 'type' => WorkstationType::OTHER, 'location' => 'Hala H - Magazyn'],
        ];

        foreach ($workstations as $ws) {
            Workstation::create(array_merge($ws, ['status' => WorkstationStatus::IDLE]));
        }

        echo " ✓ Utworzono " . count($workstations) . " stanowisk\n";

        // Przypisz operatorów
        $this->assignOperators();
    }

    private function assignOperators(): void
    {
        $workers = User::where('role', UserRole::PRODUCTION_EMPLOYEE)->get();

        if ($workers->isEmpty()) {
            echo " ⚠ Brak pracowników - pominięto przypisanie operatorów\n";
            return;
        }

        // Mapowanie pracowników do stanowisk
        $assignments = [
            // Lasery
            'LASER-01' => ['jan.laser@cserp.pl'],
            'LASER-02' => ['jan.laser@cserp.pl', 'karol.mill@cserp.pl'],

            // CNC
            'CNC-01' => ['piotr.cnc@cserp.pl'],
            'CNC-02' => ['piotr.cnc@cserp.pl'],
            'PLOTER-CNC-01' => ['bartosz.plotter@cserp.pl'],
            'PLOTER-CNC-02' => ['bartosz.plotter@cserp.pl'],
            'FREZARKA-01' => ['karol.mill@cserp.pl'],

            // Drukarnia
            'UV-FLORA' => ['maciej.uv@cserp.pl'],
            'UV-ROLAND' => ['maciej.uv@cserp.pl'],
            'SOLWENT-01' => ['tomasz.solvent@cserp.pl'],
            'SOLWENT-02' => ['tomasz.solvent@cserp.pl'],
            'LAMINATOR' => ['iwona.finish@cserp.pl'],
            'PLOTER-TNĄCY' => ['iwona.finish@cserp.pl'],

            // Montaż
            'MONTAŻ-01' => ['krzysztof.assembly@cserp.pl', 'pawel.senior@cserp.pl'],
            'MONTAŻ-02' => ['krzysztof.assembly@cserp.pl', 'wojciech.helper@cserp.pl'],
            'MONTAŻ-03' => ['pawel.senior@cserp.pl'],
            'MONTAŻ-ELEKTRO' => ['damian.electric@cserp.pl'],
            'STÓŁ-STOLARSKI' => ['marcin.carpenter@cserp.pl'],

            // Malowanie
            'KABINA-LAKIERNICZA-01' => ['agnieszka.paint@cserp.pl'],
            'KABINA-LAKIERNICZA-02' => ['jacek.lacquer@cserp.pl'],
            'SUSZARNIA' => ['agnieszka.paint@cserp.pl', 'jacek.lacquer@cserp.pl'],

            // Produkcja
            'GIĘTARKA-METAL' => ['lukasz.bending@cserp.pl'],
            'TERMOFORMOWANIE-MAŁE' => ['lukasz.bending@cserp.pl'],
            'TERMOFORMOWANIE-DUŻE' => ['lukasz.bending@cserp.pl'],

            // Inne
            'SZLIFIERNIA' => ['daniel.grinder@cserp.pl'],
            'PAKOWANIE-01' => ['sebastian.pack@cserp.pl', 'wojciech.helper@cserp.pl'],
            'PAKOWANIE-02' => ['sebastian.pack@cserp.pl'],
        ];

        $assignedCount = 0;

        foreach ($assignments as $workstationName => $emails) {
            $workstation = Workstation::where('name', $workstationName)->first();

            if (!$workstation) {
                continue;
            }

            foreach ($emails as $index => $email) {
                $user = $workers->firstWhere('email', $email);

                if ($user) {
                    $workstation->operators()->attach($user->id, [
                        'is_primary' => $index === 0
                    ]);
                    $assignedCount++;
                }
            }
        }

        echo " ✓ Przypisano $assignedCount połączeń pracownik-stanowisko\n";
    }
}
