<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('transferred_from_id')->nullable()->after('active_asset_id');
            $table->foreign('transferred_from_id')
                ->references('id')
                ->on('asset_assignments')
                ->nullOnDelete();
            $table->index('transferred_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['transferred_from_id']);
            $table->dropIndex(['transferred_from_id']);
            $table->dropColumn('transferred_from_id');
        });
    }
};
