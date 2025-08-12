<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClothingCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tシャツ',
                'icon_path' => '/icons/tshirt.svg',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ズボン',
                'icon_path' => '/icons/pants.svg',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '靴下',
                'icon_path' => '/icons/socks.svg',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ハンカチ',
                'icon_path' => '/icons/handkerchief.svg',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '肌着',
                'icon_path' => '/icons/underwear.svg',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ぼうし',
                'icon_path' => '/icons/hat.svg',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '水着セット',
                'icon_path' => '/icons/swimwear.svg',
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ビニール袋',
                'icon_path' => '/icons/plastic_bag.svg',
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Only seed if table is empty
        if (DB::table('clothing_categories')->count() === 0) {
            DB::table('clothing_categories')->insert($categories);
        }
    }
}
