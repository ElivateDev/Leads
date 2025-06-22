<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Client;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientEmail>
 */
class ClientEmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'email' => fake()->unique()->safeEmail(),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }
}
