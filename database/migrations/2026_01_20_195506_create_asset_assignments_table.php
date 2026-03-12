<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('assigned_to_type');
            $table->unsignedBigInteger('assigned_to_id');
            $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('notes')->nullable();
            $table->string('location_at_assignment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('active_asset_id')->nullable();
            $table->timestamps();

            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index('returned_at');
            $table->unique('active_asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
