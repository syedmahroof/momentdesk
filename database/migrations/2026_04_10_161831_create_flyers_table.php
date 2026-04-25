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
        Schema::create('flyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flyer_template_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('paper_size')->default('a4');
            $table->unsignedInteger('canvas_width')->default(1240);
            $table->unsignedInteger('canvas_height')->default(1754);
            $table->json('field_values')->nullable();
            $table->json('element_overrides')->nullable();
            $table->json('asset_paths')->nullable();
            $table->json('template_snapshot');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flyers');
    }
};
