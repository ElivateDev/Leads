<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeadSourceRule>
 */
class LeadSourceRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ruleTypes = ['contains', 'exact', 'regex', 'url_parameter', 'domain'];
        $matchFields = ['body', 'subject', 'url', 'from_email', 'from_domain'];
        $sources = ['website', 'phone', 'referral', 'social', 'other'];

        $ruleType = $this->faker->randomElement($ruleTypes);
        $matchField = $this->faker->randomElement($matchFields);

        // Generate appropriate rule_value based on rule_type and match_field
        $ruleValue = match ($ruleType) {
            'contains' => $this->faker->word(),
            'exact' => $this->faker->word(),
            'regex' => '[a-zA-Z0-9]+',
            'url_parameter' => 'utm_source=' . $this->faker->randomElement(['facebook', 'google', 'instagram']),
            'domain' => $this->faker->randomElement(['facebook.com', 'instagram.com', 'linkedin.com', 'google.com']),
            default => $this->faker->word(),
        };

        return [
            'client_id' => \App\Models\Client::factory(),
            'source_name' => $this->faker->randomElement($sources),
            'rule_type' => $ruleType,
            'rule_value' => $ruleValue,
            'match_field' => $matchField,
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'priority' => $this->faker->numberBetween(0, 100),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
