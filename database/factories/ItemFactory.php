<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'type' => fake()->randomElement(['lost', 'found']),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'hidden_detail' => fake()->sentence(),
            'date_event' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => fake()->randomElement(['open', 'claimed', 'returned', 'closed']),
            'storage_location' => 'Lokasi penyimpanan petugas',
        ];
    }
}
