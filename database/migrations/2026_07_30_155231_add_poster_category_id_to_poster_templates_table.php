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
        Schema::table('poster_templates', function (Blueprint $table) {
            $table->foreignId('poster_category_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poster_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('poster_category_id');
        });
    }
};
