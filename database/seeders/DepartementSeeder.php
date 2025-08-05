<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartementSeeder extends Seeder
{
    public function run()
    {

        $departements = [
            // Adamaoua
            ['nom' => 'Djérem', 'region' => 'Adamaoua'],
            ['nom' => 'Faro-et-Dahké', 'region' => 'Adamaoua'],
            ['nom' => 'Vina', 'region' => 'Adamaoua'],
            ['nom' => 'Mayo-Bélé', 'region' => 'Adamaoua'],
            ['nom' => 'La Bénoué', 'region' => 'Adamaoua'],
            ['nom' => 'Mbéré', 'region' => 'Adamaoua'],
        ];

        foreach ($departements as $departement) {


            // Insérer la ville
            DB::table('departements')->insert([
                'name' => $departement['nom'],
            ]);
        }
    }
}
