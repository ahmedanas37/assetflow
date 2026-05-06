<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table): void {
            $table->string('acceptance_token_hash', 64)->nullable()->unique()->after('transferred_from_id');
            $table->text('acceptance_token')->nullable()->after('acceptance_token_hash');
            $table->timestamp('accepted_at')->nullable()->after('acceptance_token');
            $table->string('accepted_by_name')->nullable()->after('accepted_at');
            $table->string('accepted_ip', 45)->nullable()->after('accepted_by_name');
            $table->text('accepted_user_agent')->nullable()->after('accepted_ip');
        });

        Schema::table('accessory_assignments', function (Blueprint $table): void {
            $table->string('acceptance_token_hash', 64)->nullable()->unique()->after('is_active');
            $table->text('acceptance_token')->nullable()->after('acceptance_token_hash');
            $table->timestamp('accepted_at')->nullable()->after('acceptance_token');
            $table->string('accepted_by_name')->nullable()->after('accepted_at');
            $table->string('accepted_ip', 45)->nullable()->after('accepted_by_name');
            $table->text('accepted_user_agent')->nullable()->after('accepted_ip');
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table): void {
            $table->dropUnique('asset_assignments_acceptance_token_hash_unique');
            $table->dropColumn([
                'acceptance_token_hash',
                'acceptance_token',
                'accepted_at',
                'accepted_by_name',
                'accepted_ip',
                'accepted_user_agent',
            ]);
        });

        Schema::table('accessory_assignments', function (Blueprint $table): void {
            $table->dropUnique('accessory_assignments_acceptance_token_hash_unique');
            $table->dropColumn([
                'acceptance_token_hash',
                'acceptance_token',
                'accepted_at',
                'accepted_by_name',
                'accepted_ip',
                'accepted_user_agent',
            ]);
        });
    }
};
