<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $imageIds = \App\Models\Image::pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();
        $medicaments = [
            'Paracétamol 500mg',
            'Ibuprofène 400mg',
            'Amoxicilline 500mg',
            'Aspirine 100mg',
            'Ciprofloxacine 250mg',
            'Doxycycline 100mg',
            'Metformine 850mg',
            'Insuline lente',
            'Lisinopril 10mg',
            'Amlodipine 5mg',
            'Simvastatine 20mg',
            'Atorvastatine 20mg',
            'Losartan 50mg',
            'Furosemide 40mg',
            'Prednisone 10mg',
            'Oméprazole 20mg',
            'Esoméprazole 20mg',
            'Cétirizine 10mg',
            'Loratadine 10mg',
            'Salbutamol inhalateur',
            'Formoterol inhalateur',
            'Montélukast 10mg',
            'Sertraline 50mg',
            'Fluoxétine 20mg',
            'Paroxétine 20mg',
            'Venlafaxine 75mg',
            'Amitryptiline 25mg',
            'Clonazépam 0.5mg',
            'Lorazépam 1mg',
            'Diazépam 5mg',
            'Mirtazapine 30mg',
            'Trazodone 50mg',
            'Bupropion 150mg',
            'Risperidone 2mg',
            'Olanzapine 10mg',
            'Quetiapine 25mg',
            'Haloperidol 5mg',
            'Carbamazépine 200mg',
            'Valproate de sodium 500mg',
            'Lamotrigine 100mg',
            'Topiramate 100mg',
            'Levodopa 250mg',
            'Cabergoline 0.5mg',
            'Pramipexole 0.25mg',
            'Ropinirole 1mg',
            'Donepezil 10mg',
            'Memantine 10mg',
            'Levothyroxine 100mcg',
            'Propylthiouracile 50mg',
            'Metoprolol 50mg',
            'Bisoprolol 5mg',
            'Atenolol 50mg',
            'Clopidogrel 75mg',
            'Enoxaparine 40mg',
            'Dalteparine 2500IU',
            'Sildenafil 50mg',
            'Tadalafil 10mg',
            'Vardenafil 20mg',
            'Nitroglycérine sublinguale',
            'Captopril 25mg',
            'Fentanyl patch 75mcg/h',
            'Morphine 10mg',
            'Tramadol 50mg',
            'Codeine 30mg',
            'Dexketoprofen 25mg',
            'Nimesulide 100mg',
            'Ketoprofen 100mg',
            'Piroxicam 20mg',
            'Indométhacine 25mg',
            'Célécoxib 200mg',
            'Meloxicam 15mg',
            'Rofecoxib 12.5mg',
            'Etoricoxib 60mg',
            'Hydroxyzine 25mg',
            'Promethazine 25mg',
            'Dimenhydrinate 50mg',
            'Ondansetron 8mg',
            'Domperidone 10mg',
            'Loperamide 2mg',
            'Psyllium 3g',
            'Methylcellulose 2g',
            'Sennosides 8.6mg',
            'Bisacodyl 5mg',
            'Polyethylene glycol 17g',
            'Calcium carbonate 500mg',
            'Magnesium hydroxide 400mg',
            'Ferrous sulfate 325mg',
            'Folic acid 5mg',
            'Vitamin D3 1000UI',
            'Vitamin B12 1000mcg',
            'Vitamin C 500mg',
            'Zinc 15mg',
            'Selenium 100mcg',
            'Omega-3 1000mg'
        ];
        foreach ($medicaments as $medicament) {
            DB::table('products')->insert([
                'intitule'           => $medicament,
                'reference'          => strtoupper($faker->bothify('REF-####-??')),
                'price'              => $faker->randomFloat(2, 100, 10000),
                'lot'                => strtoupper($faker->bothify('LOT-#####')),
                'date_fabrication'   => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'date_peremption'    => $faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
                'financement'        => $faker->randomElement(['Subventionné', 'Partenaire', 'ONG', 'Privé']),
                'utilisateur_cible'  => $faker->randomElement(['Hôpitaux', 'Pharmacies', 'Laboratoires', 'Centres de santé']),
                'quantite'           => $faker->randomFloat(2, 1, 500),
                'unite'              => $faker->randomElement(['mg', 'ml', 'kg', 'L']),
                'poids'              => $faker->optional()->randomElement(['10kg', '1.5L', '500g']),
                'category_id' => $faker->randomElement($categoryIds),
               'image_id' => $faker->optional()->randomElement($imageIds),
                'publish'=>$faker->boolean(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}

