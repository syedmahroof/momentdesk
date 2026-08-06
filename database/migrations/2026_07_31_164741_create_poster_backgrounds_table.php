<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['background_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_backgrounds');
    }
};
