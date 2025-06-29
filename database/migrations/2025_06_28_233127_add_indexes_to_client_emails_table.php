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
        Schema::table('client_emails', function (Blueprint $table) {
            // Add indexes for better query performance
            $table->index(['is_active', 'rule_type']);
            $table->index(['email', 'is_active']);
            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_emails', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'rule_type']);
            $table->dropIndex(['email', 'is_active']);
            $table->dropIndex(['rule_type', 'is_active']);
        });
    }
};
