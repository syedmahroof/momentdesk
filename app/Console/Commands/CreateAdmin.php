<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {--name= : The admin name}
        {--email= : The admin email}
        {--password= : The admin password, prompted for when omitted}';

    protected $description = 'Create a super admin account for the admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $plainPassword = $this->option('password') ?: password('Password', required: true);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
        ]);

        $this->info("Admin {$admin->email} created. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
