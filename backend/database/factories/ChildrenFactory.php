<?php

namespace Database\Factories;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildrenFactory extends Factory
{
    protected $model = Children::class;

    public function definition(): array
    {
        return [
            'user_group_id' => UserGroup::factory(),
            'name' => $this->faker->firstName(),
        ];
    }
}