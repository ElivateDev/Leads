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
            $table->dropColumn('rule_type');
        });

        Schema::table('client_emails', function (Blueprint $table) {
            $table->enum('rule_type', ['email_match', 'custom_rule', 'combined_rule'])
                ->default('email_match')
                ->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_emails', function (Blueprint $table) {
            $table->dropColumn('rule_type');
        });

        Schema::table('client_emails', function (Blueprint $table) {
            $table->enum('rule_type', ['email_match', 'custom_rule'])
                ->default('email_match')
                ->after('client_id');
        });
    }
};
