<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_dates')) {
            Schema::create('customer_dates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['birthday', 'wedding', 'work', 'custom'])->default('birthday');
                $table->string('title')->nullable();
                $table->date('date');
                $table->unsignedTinyInteger('reminder_days_before')->default(1);
                $table->boolean('active')->default(true);
                $table->boolean('auto_send')->default(false);
                $table->timestamps();

                $table->index(['tenant_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_dates');
    }
};
