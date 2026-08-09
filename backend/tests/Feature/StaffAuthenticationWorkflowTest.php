<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAuthenticationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_staff_registration_creates_a_pending_staff_account(): void
    {
        config(['bloodcare.staff_registration_open' => true]);

        $response = $this->post(route('bloodcare.staff.register.store'), [
            'name' => 'New Staff Member',
            'email' => 'new.staff@hospital.org',
            'phone' => '09123456789',
            'job_title' => 'Blood Bank Technician',
            'workplace' => 'Yangon Central Blood Centre',
            'registration_note' => 'Assigned to the morning collection team.',
            'password' => 'StrongPass9',
            'password_confirmation' => 'StrongPass9',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('backpack.auth.login'));
        $response->assertSessionHas('status');

        $staff = User::query()->where('email', 'new.staff@hospital.org')->firstOrFail();

        $this->assertSame(User::ROLE_STAFF, $staff->role);
        $this->assertSame(User::APPROVAL_PENDING, $staff->approval_status);
        $this->assertFalse((bool) $staff->is_banned);
        $this->assertTrue(Hash::check('StrongPass9', $staff->password));
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => User::class,
            'subject_id' => $staff->id,
            'action' => 'Staff registration submitted',
            'result' => 'Pending',
        ]);
    }

    public function test_staff_registration_shows_live_password_requirements_before_submit(): void
    {
        config(['bloodcare.staff_registration_open' => true]);

        $response = $this->get(route('bloodcare.staff.register'));

        $response->assertOk();
        $response->assertSee('id="bc-register-password-strength"', false);
        $response->assertSee('id="bc-register-password-match"', false);
        $response->assertSee('id="bc-register-password-requirements"', false);
        $response->assertSee('data-register-password-rule="length"', false);
        $response->assertSee('data-register-password-rule="lower"', false);
        $response->assertSee('data-register-password-rule="upper"', false);
        $response->assertSee('data-register-password-rule="number"', false);
        $response->assertSee('aria-describedby="bc-register-password-strength bc-register-password-requirements"', false);
        $response->assertSee('js/bloodcare-auth.js', false);
    }

    public function test_pending_staff_account_is_blocked_from_the_backpack_workspace(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
            'is_banned' => false,
        ]);

        backpack_auth()->login($staff);

        $response = $this->get(route('bloodcare.admin.donors'));

        $response->assertRedirect(backpack_url('login'));
        $response->assertSessionHasErrors(config('backpack.base.authentication_column', 'email'));
        $this->assertGuest('web');
    }

    public function test_pending_staff_login_is_rejected_before_the_workspace_renders(): void
    {
        $staff = User::factory()->create([
            'email' => 'pending.staff@hospital.org',
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
            'is_banned' => false,
        ]);

        $response = $this->post(route('backpack.auth.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('backpack.auth.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_system_administrator_can_approve_staff_and_enable_workspace_access(): void
    {
        $administrator = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
            'is_banned' => false,
        ]);

        backpack_auth()->login($administrator);

        $approval = $this->patchJson(route('bloodcare.admin.users.approval', $staff), [
            'approval' => 'Approved',
        ]);

        $approval->assertOk()->assertJsonFragment([
            'message' => 'Staff registration approved.',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_by' => $administrator->id,
            'is_banned' => false,
        ]);

        backpack_auth()->logout();

        $login = $this->post(route('backpack.auth.login'), [
            'email' => $staff->refresh()->email,
            'password' => 'password',
        ]);

        $login->assertRedirect(backpack_url('dashboard'));
        $this->assertAuthenticatedAs($staff, 'web');
        $this->get(route('bloodcare.admin.donors'))->assertOk();
    }

    public function test_approved_staff_can_use_every_operational_page_but_cannot_manage_users(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
        $target = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'is_banned' => false,
        ]);

        // Reproduce the real login form using BloodCare's one canonical web
        // guard. This catches session mismatches that direct login calls hide.
        $this->withSession([
            'url.intended' => route('bloodcare.admin.inventory'),
        ]);

        $login = $this->post(route('backpack.auth.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $login->assertRedirect(backpack_url('dashboard'));
        $login->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($staff, 'web');
        $this->assertSame('web', config('backpack.base.guard'));
        // Backpack registers a compatibility "backpack" guard at runtime even
        // when BloodCare explicitly uses "web". Verify that the Backpack
        // helper resolves to the canonical web guard instead of expecting the
        // unused compatibility configuration to be absent.
        $this->assertSame(app('auth')->guard('web'), backpack_auth());
        $this->assertTrue(backpack_auth()->check());
        $this->assertSame($staff->id, backpack_user()?->id);
        $this->assertFalse($staff->canManageUsers());
        $this->assertFalse((bool) config('backpack.base.setup_auth_routes'));

        foreach ([
            backpack_url('dashboard'),
            route('bloodcare.admin.donors'),
            route('bloodcare.admin.cards'),
            route('bloodcare.admin.donations'),
            route('bloodcare.admin.inventory'),
            route('bloodcare.admin.appointments'),
            route('bloodcare.admin.reports'),
            route('bloodcare.admin.history'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $dashboard = $this->get(backpack_url('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('bp-layout="vertical"', false);
        $dashboard->assertSee('data-bc-workspace-role="staff"', false);
        $dashboard->assertSee('bc-sidebar-brand', false);
        $dashboard->assertSee('bc-admin-page', false);
        $dashboard->assertSee('bc-dashboard', false);
        $dashboard->assertSee($staff->name);
        $dashboard->assertSee(backpack_url('donors'), false);
        $dashboard->assertDontSee(backpack_url('users'), false);

        $inventory = $this->get(route('bloodcare.admin.inventory'));
        $inventory->assertOk();
        $inventory->assertSee('bc-admin-page', false);
        $inventory->assertSee($staff->name);

        $this->get(route('bloodcare.admin.users'))->assertForbidden();
        $this->patchJson(route('bloodcare.admin.users.role', $target), [
            'role' => 'Admin',
        ])->assertForbidden();
        $this->patchJson(route('bloodcare.admin.users.status', $target), [
            'is_banned' => true,
        ])->assertForbidden();
        $this->patchJson(route('bloodcare.admin.users.approval', $target), [
            'approval' => 'Approved',
        ])->assertForbidden();
    }

    public function test_newly_promoted_administrator_can_login_and_manage_users(): void
    {
        $administrator = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
        $newAdministrator = User::factory()->create([
            'email' => 'new.administrator@hospital.org',
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
            'is_banned' => false,
        ]);

        backpack_auth()->login($administrator);

        $this->patchJson(route('bloodcare.admin.users.approval', $newAdministrator), [
            'approval' => 'Approved',
        ])->assertOk();
        $this->patchJson(route('bloodcare.admin.users.role', $newAdministrator), [
            'role' => 'Admin',
        ])->assertOk();

        backpack_auth()->logout();

        $login = $this->post(route('backpack.auth.login'), [
            'email' => $newAdministrator->refresh()->email,
            'password' => 'password',
        ]);

        $login->assertRedirect(backpack_url('dashboard'));
        $this->assertAuthenticatedAs($newAdministrator, 'web');
        $this->assertTrue($newAdministrator->refresh()->canManageUsers());

        $dashboard = $this->get(backpack_url('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee(backpack_url('users'), false);
        $dashboard->assertSee('data-bc-workspace-role="admin"', false);
        $dashboard->assertDontSee('gravatar.com', false);

        $this->get(route('bloodcare.admin.users'))->assertOk();
    }

    public function test_administrator_can_approve_a_pending_user_with_the_selected_role_atomically(): void
    {
        $administrator = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);
        $pendingUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'is_banned' => false,
        ]);

        backpack_auth()->login($administrator);

        $response = $this->patchJson(route('bloodcare.admin.users.approval', $pendingUser), [
            'approval' => 'Approved',
            'role' => 'Admin',
        ]);

        $response->assertOk()->assertJsonPath('role', User::ROLE_ADMIN);
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_by' => $administrator->id,
            'is_banned' => false,
        ]);
        $this->assertNotNull($pendingUser->fresh()->approved_at);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => User::class,
            'subject_id' => $pendingUser->id,
            'action' => 'Staff registration approved',
            'result' => 'Approved',
        ]);
    }

    public function test_system_and_laboratory_workspaces_are_separated_while_inventory_is_shared(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($administrator);

        $this->get(route('bloodcare.admin.inventory'))->assertOk();
        $this->get(route('bloodcare.lab.dashboard'))->assertForbidden();
        $this->get(route('bloodcare.lab.laboratory'))->assertForbidden();

        backpack_auth()->logout();
        $labStaff = User::factory()->create(['role' => User::ROLE_LAB_STAFF]);
        backpack_auth()->login($labStaff);

        foreach ([
            route('bloodcare.lab.dashboard'),
            route('bloodcare.lab.laboratory'),
            route('bloodcare.lab.components'),
            route('bloodcare.lab.inventory'),
            route('bloodcare.lab.history'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('bloodcare.admin.donors'))->assertForbidden();
        $this->get(backpack_url('dashboard'))->assertForbidden();
    }

    public function test_users_page_exposes_five_management_categories_and_keeps_hospital_accounts_separate(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_STAFF]);
        User::factory()->create(['role' => User::ROLE_LAB_STAFF]);
        User::factory()->create(['role' => User::ROLE_LAB_ADMIN]);
        User::factory()->create(['role' => User::ROLE_USER]);
        User::factory()->create(['email' => 'portal-only@hospital.test', 'role' => User::ROLE_HOSPITAL]);
        backpack_auth()->login($administrator);

        $response = $this->get(route('bloodcare.admin.users'));
        $response->assertOk()
            ->assertSee(__('bloodcare.users.user_role'))
            ->assertSee(__('bloodcare.users.system_staff_role'))
            ->assertSee(__('bloodcare.users.system_admin_role'))
            ->assertSee(__('bloodcare.users.lab_staff_role'))
            ->assertSee(__('bloodcare.users.lab_admin_role'))
            ->assertDontSee('portal-only@hospital.test');
    }

    public function test_profile_edits_staff_database_fields_and_keeps_password_change_separate(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_LAB_STAFF,
            'phone' => '09111111111',
            'job_title' => 'Lab Technician',
            'workplace' => 'Central Laboratory',
            'registration_note' => 'Initial note',
        ]);
        backpack_auth()->login($staff);

        $this->get(route('backpack.account.info'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('name="job_title"', false)
            ->assertSee('name="workplace"', false)
            ->assertSee('name="registration_note"', false)
            ->assertSee('data-bc-password-panel-toggle', false)
            ->assertSee('data-bc-password-panel', false);

        $this->post(route('backpack.account.info.store'), [
            'name' => 'Updated Lab Staff',
            'email' => $staff->email,
            'phone' => '09222222222',
            'job_title' => 'Senior Lab Technician',
            'workplace' => 'BloodCare Laboratory',
            'registration_note' => 'Component processing team',
        ])->assertRedirect(route('backpack.account.info'));

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Updated Lab Staff',
            'phone' => '09222222222',
            'job_title' => 'Senior Lab Technician',
            'workplace' => 'BloodCare Laboratory',
            'role' => User::ROLE_LAB_STAFF,
        ]);

        $this->post(route('backpack.account.password'), [
            'old_password' => 'password',
            'new_password' => 'NewStrong9!',
            'confirm_password' => 'NewStrong9!',
        ])->assertRedirect(route('backpack.account.info'));

        $this->assertTrue(Hash::check('NewStrong9!', $staff->fresh()->password));
    }

}
