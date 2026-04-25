<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flyer_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('custom');
            $table->string('paper_size')->default('a4');
            $table->unsignedInteger('canvas_width')->default(1240);
            $table->unsignedInteger('canvas_height')->default(1754);
            $table->string('background_type')->default('color');
            $table->string('background_color')->nullable();
            $table->string('background_image_path')->nullable();
            $table->json('elements');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flyer_templates');
    }
};
