<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the admins table and move existing super admins out of `users`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $superAdmins = DB::table('users')->where('role', 'super_admin')->get();

        foreach ($superAdmins as $superAdmin) {
            $alreadyAnAdmin = DB::table('admins')->where('email', $superAdmin->email)->exists();

            if ($alreadyAnAdmin) {
                continue;
            }

            DB::table('admins')->insert([
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'password' => $superAdmin->password,
                'created_at' => $superAdmin->created_at,
                'updated_at' => $superAdmin->updated_at,
            ]);
        }

        DB::table('users')->where('role', 'super_admin')->delete();
    }

    /**
     * Move admins back into `users` as super admins, then drop the table.
     */
    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('users', 'role')) {
            foreach (DB::table('admins')->get() as $admin) {
                DB::table('users')->insertOrIgnore([
                    'tenant_id' => null,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'password' => $admin->password,
                    'role' => 'super_admin',
                    'created_at' => $admin->created_at,
                    'updated_at' => $admin->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('admins');
    }
};
