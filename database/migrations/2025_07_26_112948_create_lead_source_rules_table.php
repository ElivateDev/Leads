<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lead_source_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('source_name'); // website, phone, referral, social, other
            $table->enum('rule_type', ['contains', 'exact', 'regex', 'url_parameter', 'domain']);
            $table->string('rule_value', 500);
            $table->enum('match_field', ['body', 'subject', 'url', 'from_email', 'from_domain']);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher numbers = higher priority
            $table->string('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['client_id', 'is_active', 'priority']);
            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_source_rules');
    }
};
