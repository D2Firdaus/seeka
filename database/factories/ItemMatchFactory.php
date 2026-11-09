<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemMatch>
 */
class ItemMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lost_item_id' => Item::factory()->state(['type' => 'lost']),
            'found_item_id' => Item::factory()->state(['type' => 'found']),
            'jw_score' => null,
            'metadata_score' => null,
            'bgem3_sparse' => null,
            'bgem3_dense' => null,
            'bge_reranker' => null,
            'final_score' => 1.0,
            'pipeline_version' => 'manual',
            'status' => 'new',
            'notified_at' => null,
        ];
    }
}
