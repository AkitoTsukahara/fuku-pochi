<?php

namespace Database\Factories;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockItemFactory extends Factory
{
    protected $model = StockItem::class;

    public function definition(): array
    {
        return [
            'child_id' => Children::factory(),
            'clothing_category_id' => $this->faker->numberBetween(1, 8), // Assuming 8 categories from seeder
            'current_count' => $this->faker->numberBetween(0, 10),
        ];
    }
}