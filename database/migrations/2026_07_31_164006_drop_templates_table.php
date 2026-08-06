<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('message_logs', 'template_id')) {
            Schema::table('message_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('template_id');
            });
        }

        Schema::dropIfExists('templates');
    }

    public function down(): void
    {
        if (! Schema::hasTable('templates')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', ['birthday', 'wedding', 'work', 'custom'])->default('birthday');
                $table->enum('channel', ['whatsapp', 'email'])->default('whatsapp');
                $table->string('subject')->nullable();
                $table->text('content');
                $table->json('variables')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index('tenant_id');
            });
        }

        if (! Schema::hasColumn('message_logs', 'template_id')) {
            Schema::table('message_logs', function (Blueprint $table) {
                $table->foreignId('template_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            });
        }
    }
};
