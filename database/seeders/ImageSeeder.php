<?php


namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Image;
use Faker\Factory as Faker;

class ImageSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        foreach (range(1, 20) as $i) {
            Image::create([
                'src' => $faker->imageUrl(640, 480, 'products', true, 'Faker'),
                'name' => $faker->words(2, true),
            ]);
        }
    }
}
