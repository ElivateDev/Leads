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
        Schema::create('campaign_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('campaign_name');
            $table->string('rule_type')->default('contains'); // contains, regex, url_parameter, exact
            $table->text('rule_value'); // The value to match against
            $table->string('match_field')->default('body'); // body, subject, url, from_email
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher numbers = higher priority
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index(['rule_type', 'match_field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_rules');
    }
};
