<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $name = trim((string) config('bloodcare.bootstrap_admin.name'));
        $email = strtolower(trim((string) config('bloodcare.bootstrap_admin.email')));
        $password = (string) config('bloodcare.bootstrap_admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'Bootstrap administrator not created. Set BLOODCARE_BOOTSTRAP_ADMIN_EMAIL and BLOODCARE_BOOTSTRAP_ADMIN_PASSWORD to opt in.'
            );

            return;
        }

        $credentials = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        if (User::where('email', $credentials['email'])->exists()) {
            $this->command?->info('Bootstrap administrator already exists; the account was left unchanged.');

            return;
        }

        User::forceCreate([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'email_verified_at' => now(),
            'password' => $credentials['password'],
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
    }
}
