<?php

namespace Database\Factories;

use App\Models\EmailProcessingLog;
use App\Models\Client;
use App\Models\Lead;
use App\Models\ClientEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailProcessingLog>
 */
class EmailProcessingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['email_received', 'rule_matched', 'rule_failed', 'lead_created', 'lead_duplicate', 'notification_sent', 'error'];
        $statuses = ['success', 'failed', 'skipped'];
        $ruleTypes = ['email_match', 'custom_rule', 'combined_rule'];

        $type = fake()->randomElement($types);
        $status = match ($type) {
            'email_received', 'rule_matched', 'lead_created', 'notification_sent' => 'success',
            'rule_failed', 'error' => 'failed',
            'lead_duplicate' => 'skipped',
            default => fake()->randomElement($statuses),
        };

        return [
            'email_id' => fake()->uuid(),
            'from_address' => fake()->email(),
            'subject' => fake()->sentence(6),
            'type' => $type,
            'status' => $status,
            'client_id' => Client::factory(),
            'lead_id' => $type === 'lead_created' ? Lead::factory() : null,
            'rule_id' => in_array($type, ['rule_matched', 'rule_failed']) ? ClientEmail::factory() : null,
            'rule_type' => in_array($type, ['rule_matched', 'rule_failed']) ? fake()->randomElement($ruleTypes) : null,
            'message' => fake()->sentence(10),
            'details' => [
                'sample_key' => fake()->word(),
                'processing_time' => fake()->randomFloat(2, 0.1, 5.0),
                'timestamp' => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            ],
            'processed_at' => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Create an email received log entry
     */
    public function emailReceived(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'email_received',
            'status' => 'success',
            'message' => 'Email received from ' . $attributes['from_address'],
            'details' => [
                'body_length' => fake()->numberBetween(100, 5000),
                'has_attachments' => fake()->boolean(),
            ],
        ]);
    }

    /**
     * Create a rule matched log entry
     */
    public function ruleMatched(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'rule_matched',
            'status' => 'success',
            'rule_id' => ClientEmail::factory(),
            'rule_type' => fake()->randomElement(['email_match', 'custom_rule', 'combined_rule']),
            'message' => 'Distribution rule matched successfully',
        ]);
    }

    /**
     * Create a lead created log entry
     */
    public function leadCreated(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'lead_created',
            'status' => 'success',
            'lead_id' => Lead::factory(),
            'message' => 'Lead created successfully',
            'details' => [
                'lead_name' => fake()->name(),
                'lead_source' => fake()->randomElement(['website', 'social', 'other']),
            ],
        ]);
    }

    /**
     * Create an error log entry
     */
    public function error(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'error',
            'status' => 'failed',
            'message' => 'Error processing email: ' . fake()->sentence(),
            'details' => [
                'error_code' => fake()->randomElement(['PARSE_ERROR', 'DB_ERROR', 'VALIDATION_ERROR']),
                'exception_class' => 'Exception',
                'stack_trace' => fake()->text(200),
            ],
        ]);
    }
}
