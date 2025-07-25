<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email_id')->nullable(); // IMAP message ID
            $table->string('from_address');
            $table->string('subject')->nullable();
            $table->enum('type', [
                'email_received',
                'rule_matched',
                'rule_failed',
                'lead_created',
                'lead_duplicate',
                'notification_sent',
                'error'
            ]);
            $table->enum('status', ['success', 'failed', 'skipped']);
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('client_emails')->cascadeOnDelete();
            $table->string('rule_type')->nullable(); // email_match, custom_rule, combined_rule
            $table->text('message');
            $table->json('details')->nullable(); // Additional data like rule conditions, error traces, etc.
            $table->timestamp('processed_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['from_address', 'processed_at']);
            $table->index(['client_id', 'processed_at']);
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_processing_logs');
    }
};
