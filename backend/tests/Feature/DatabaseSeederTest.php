<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_create_a_default_login_without_explicit_credentials(): void
    {
        config()->set('bloodcare.bootstrap_admin.email');
        config()->set('bloodcare.bootstrap_admin.password');

        $this->seed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_creates_an_opt_in_administrator_without_overwriting_it_later(): void
    {
        config()->set('bloodcare.bootstrap_admin', [
            'name' => 'BloodCare Administrator',
            'email' => 'admin@bloodcare.test',
            'password' => 'Initial!Password123',
        ]);

        $this->seed();

        $administrator = User::where('email', 'admin@bloodcare.test')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $administrator->role);
        $this->assertSame(User::APPROVAL_APPROVED, $administrator->approval_status);
        $this->assertFalse($administrator->is_banned);
        $this->assertTrue(Hash::check('Initial!Password123', $administrator->password));

        $administrator->update([
            'password' => 'Manual!Password789',
            'is_banned' => true,
        ]);
        config()->set('bloodcare.bootstrap_admin.password', 'Replacement!Password456');

        $this->seed();

        $administrator->refresh();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($administrator->is_banned);
        $this->assertTrue(Hash::check('Manual!Password789', $administrator->password));
        $this->assertFalse(Hash::check('Replacement!Password456', $administrator->password));
    }
}
