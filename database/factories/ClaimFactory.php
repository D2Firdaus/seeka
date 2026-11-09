<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'found_item_id' => Item::factory()->state(['type' => 'found']),
            'claimant_id' => User::factory(),
            'lost_item_id' => null,
            'proof_description' => fake()->paragraph(),
            'proof_image' => null,
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'admin_note' => null,
        ];
    }

    /**
     * State klaim disetujui.
     */
    public function approved(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'verified_by' => $admin ?? User::factory()->state(['role' => 'admin']),
            'verified_at' => now(),
            'admin_note' => 'Klaim disetujui.',
        ]);
    }

    /**
     * State klaim ditolak.
     */
    public function rejected(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'verified_by' => $admin ?? User::factory()->state(['role' => 'admin']),
            'verified_at' => now(),
            'admin_note' => 'Bukti kepemilikan tidak sesuai.',
        ]);
    }
}
