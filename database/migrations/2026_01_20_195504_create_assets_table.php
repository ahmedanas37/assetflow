<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('serial')->unique()->nullable();
            $table->foreignId('asset_model_id')->constrained('asset_models');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('status_label_id')->constrained('status_labels');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->date('warranty_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status_label_id');
            $table->index('asset_model_id');
            $table->index('category_id');
            $table->index('location_id');
            $table->index('assigned_to_user_id');
            $table->index('warranty_end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
