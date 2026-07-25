<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poster_templates', function (Blueprint $table) {
            $table->string('type')->nullable()->after('category');
        });

        // Collapse to two categories: gold_price and poster (with a free-form type).
        DB::table('poster_templates')->where('category', 'gold_rate')->update(['category' => 'gold_price', 'type' => 'Gold rate']);
        DB::table('poster_templates')->where('category', 'birthday')->update(['category' => 'poster', 'type' => 'Birthday']);
        DB::table('poster_templates')->whereNotIn('category', ['gold_price', 'poster'])->update(['category' => 'poster']);
    }

    public function down(): void
    {
        Schema::table('poster_templates', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
