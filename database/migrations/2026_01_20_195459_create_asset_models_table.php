<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('manufacturers');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->string('model_number')->nullable();
            $table->unsignedInteger('depreciation_months')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['manufacturer_id', 'name', 'model_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_models');
    }
};
