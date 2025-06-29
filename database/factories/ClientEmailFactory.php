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
            'rule_type' => 'email_match',
            'email' => fake()->unique()->safeEmail(),
            'custom_conditions' => null,
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Create an email match rule.
     * @return ClientEmailFactory
     */
    public function emailMatch(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_type' => 'email_match',
            'email' => fake()->unique()->safeEmail(),
            'custom_conditions' => null,
        ]);
    }

    /**
     * Create a custom rule.
     * @return ClientEmailFactory
     */
    public function customRule(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_type' => 'custom_rule',
            'email' => null,
            'custom_conditions' => fake()->randomElement([
                'Source: Facebook',
                'rep: henry',
                'Source: Google AND type: lead',
                'Source: Facebook OR Source: Instagram',
            ]),
        ]);
    }

    /**
     * Create a combined rule.
     * @return ClientEmailFactory
     */
    public function combinedRule(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_type' => 'combined_rule',
            'email' => fake()->randomElement([
                fake()->unique()->safeEmail(),
                '@' . fake()->domainName(),
            ]),
            'custom_conditions' => fake()->randomElement([
                'Source: Facebook AND rep: henry',
                'property_type: commercial',
                'Source: Zillow AND agent: john',
                'campaign: summer2023',
            ]),
        ]);
    }
}
