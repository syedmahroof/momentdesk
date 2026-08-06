<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('message_logs')->where('channel', 'sms')->delete();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE message_logs MODIFY channel ENUM('whatsapp', 'email') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE message_logs MODIFY channel ENUM('whatsapp', 'email', 'sms') NOT NULL");
    }
};
