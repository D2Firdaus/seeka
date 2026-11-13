<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Handover;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Handover>
 */
class HandoverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'claim_id' => Claim::factory()->approved(),
            'handed_by' => User::factory(),
            'received_by' => User::factory(),
            'handover_date' => now(),
            'photo' => null,
            'note' => null,
        ];
    }
}
