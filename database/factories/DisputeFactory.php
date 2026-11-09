<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
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
            'reported_by' => User::factory(),
            'reason' => fake()->paragraph(),
            'status' => 'open',
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * State banding disetujui (resolved).
     */
    public function resolved(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => 'resolved',
            'resolved_by' => $admin ?? User::factory()->state(['role' => 'admin']),
            'resolved_at' => now(),
        ]);
    }

    /**
     * State banding ditolak (dismissed).
     */
    public function dismissed(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => 'dismissed',
            'resolved_by' => $admin ?? User::factory()->state(['role' => 'admin']),
            'resolved_at' => now(),
        ]);
    }
}
