<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessory_id')->constrained('accessories')->cascadeOnDelete();
            $table->string('assigned_to_type');
            $table->unsignedBigInteger('assigned_to_id');
            $table->string('assigned_to_label')->nullable();
            $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->string('location_at_assignment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index('returned_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessory_assignments');
    }
};
