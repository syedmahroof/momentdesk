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
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone');
            $table->string('logo_light_path')->nullable()->after('address');
            $table->string('logo_dark_path')->nullable()->after('logo_light_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['address', 'logo_light_path', 'logo_dark_path']);
        });
    }
};
