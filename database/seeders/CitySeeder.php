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
                'chef_lieu' => 'Meiganga',
                'localities' => [
                    'Meiganga', 'Ngaoundal', 'Nganha', 'Mbang', 'Ngoulemakong', 'Belel',
                    'Bandjoun', 'Boulougou', 'Doumé', 'Beka', 'Kika',
                ],
            ],
            [
                'department' => 'Djerem',
                'chef_lieu' => 'Tibati',
                'localities' => [
                    'Tibati', 'Bélabo', 'Garoua-Béïla', 'Ngoura', 'Mbe', 'Djohong', 'Lomie',
                    'Lassa', 'Mbalmayo', 'Sangmelima', 'Madjam', 'Tignère', 'Foulbé', 'Woulé',
                ],
            ],
            [
                'department' => 'Haute-Sanaga',
                'chef_lieu' => 'Banyo',
                'localities' => [
                    'Banyo', 'Tibati', 'Beka', 'Tignère', 'Mingam', 'Koundou', 'Ngaoundéré',
                    'Galim', 'Tchamba', 'Tcheboa', 'Nganha', 'Boulounga',
                ],
            ],
            [
                'department' => 'Lom et Djérem',
                'chef_lieu' => 'Tignère',
                'localities' => [
                    'Ngaoundéré', 'Mbe', 'Nganha', 'Loulou', 'Mbang', 'Goudjila', 'Djerem',
                    'Missafou', 'Ngaoundal', 'Belel',
                ],
            ],
            [
                'department' => 'Mefou-et-Akono',
                'chef_lieu' => 'Ngoumou',
                'localities' => [
                    'Ngoumou', 'Mbankomo', 'Nkolndongo', 'Akono', 'Ebolowa',
                    'Nkolbisson', 'Obala', 'Elig-Mfomo', 'Nyom', 'Nkolmot',
                ],
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
