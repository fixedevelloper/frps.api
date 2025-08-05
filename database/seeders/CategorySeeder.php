<?php


namespace Database\Seeders;


use App\Models\Image;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $imageIds = Image::pluck('id')->toArray();
        $familiesMedicaments = [
            'Antibiotiques',
            'Antiviraux',
            'Antifongiques',
            'Analgésiques',
            'Anti-inflammatoires',
            'Antihypertenseurs',
            'Antidiabétiques',
            'Antalgiques',
            'Antiparasitaires',
            'Vasodilatateurs',
            'Bronchodilatateurs',
            'Anticoagulants',
            'Antiplatelets',
            'Corticostéroïdes',
            'Diurétiques',
            'Laxatifs',
            'Antispasmodiques',
            'Antiemétiques',
            'Antipsychotiques',
            'Antidépresseurs',
            'Anxiolytiques',
            'Hypnotiques',
            'Sedatifs',
            'Antihistaminiques',
            'Antacides',
            'Proton Pump Inhibitors',
            'Statines',
            'Fibrates',
            'Bêtabloquants',
            'Inhibiteurs de l\'ACE',
            'Inhibiteurs de la 5-HT3',
            'Sédatifs',
            'Anticholinergiques',
            'Vitamines',
            'Suppéments minéraux',
            'Immunosuppresseurs',
            'Biothérapies',
            'Antigénétiques',
            'Anticancéreux',
            'Agents de contraste',
            'Antirétroviraux',
            'Médicaments pour l\'ostéoporose',
            'Médicaments pour l\'asthme',
            'Médicaments pour la dépression',
            'Médicaments pour l\'épilepsie',
            'Médicaments pour la schizophrénie',
            'Médicaments pour le Parkinson',
            'Médicaments pour la thyroïde',
            'Médicaments pour la prostate',
            'Médicaments pour la douleur chronique'
        ];
         // 10 sous-catégories
        $parentIds = Category::pluck('id')->toArray();

        foreach (range(1, 50) as $i) {
            Category::create([
                'intitule' => $faker->unique()->randomElement($familiesMedicaments),
                'parent_id' => $faker->randomElement($parentIds),
                'image_id' => $faker->optional()->randomElement($imageIds),
            ]);
        }
    }
}

