<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run()
    {
        $adamaouaRegions = [

                [
                    'department' => 'Vina',
                    'chef_lieu'  => 'Ngaoundéré',
                    'localities' => ['Ngaoundéré I', 'Ngaoundéré II', 'Ngaoundéré III', 'Belel', 'Mbe', 'Ngan-Ha', 'Nyambaka', 'Martap'],
                ],
                [
                    'department' => 'Djerem',
                    'chef_lieu'  => 'Tibati',
                    'localities' => ['Tibati', 'Ngaoundal'],
                ],
                [
                    'department' => 'Faro-et-Déo',
                    'chef_lieu'  => 'Tignère',
                    'localities' => ['Tignère', 'Galim-Tignère', 'Mayo-Baléo', 'Kontcha'],
                ],
                [
                    'department' => 'Mayo-Banyo',
                    'chef_lieu'  => 'Banyo',
                    'localities' => ['Banyo', 'Bankim', 'Mayo-Darlé'],
                ],
                [
                    'department' => 'Mbéré',
                    'chef_lieu'  => 'Meiganga',
                    'localities' => ['Meiganga', 'Djohong', 'Ngaoui', 'Dir'],
                ],

        ];

        // 1. Insertion des départements
        foreach ($adamaouaRegions as $region) {
            // Vérifier si le département existe déjà pour éviter doublons
            $exists = DB::table('departements')->where('name', $region['department'])->exists();

            if (!$exists) {
                DB::table('departements')->insert([
                    'name' => $region['department'],
                    //'chef_lieu' => $region['chef_lieu'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Récupérer les ids des départements après insertion
        $departements = Departement::pluck('id', 'name')->toArray();

        // 3. Préparer les villes
        $villes = [];
        foreach ($adamaouaRegions as $region) {
            foreach ($region['localities'] as $ville) {
                $villes[] = [
                    'nom' => $ville,
                    'departement' => $region['department'],
                ];
            }
        }

        // 4. Insertion des villes
        foreach ($villes as $ville) {
            if (!isset($departements[$ville['departement']])) {
                echo "Département non trouvé : " . $ville['departement'] . PHP_EOL;
                continue;
            }

            $departementId = $departements[$ville['departement']];

            // Éviter d'insérer plusieurs fois la même ville dans le même département
            $exists = DB::table('cities')
                ->where('name', $ville['nom'])
                ->where('departement_id', $departementId)
                ->exists();

            if (!$exists) {
                DB::table('cities')->insert([
                    'name' => $ville['nom'],
                    'departement_id' => $departementId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }


}
