<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_enrol_with_qr_and_secret_is_encrypted_at_rest(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($staff);

        $start = $this->post(route('two-factor.setup.start'), ['current_password' => 'password']);
        $start->assertRedirect(route('two-factor.setup'));
        $pending = session()->get('two_factor.setup');
        $this->assertIsArray($pending);
        $secret = (string) $pending['secret'];

        $this->get(route('two-factor.setup'))
            ->assertOk()
            ->assertSee('data-bc-two-factor-qr=', false)
            ->assertSee('vendor/bloodcare-qrcode/qrcode.js', false)
            ->assertSee($secret);

        $service = app(TwoFactorAuthenticationService::class);
        $confirm = $this->post(route('two-factor.confirm'), ['code' => $service->currentCode($secret)]);
        $confirm->assertRedirect(route('two-factor.recovery-codes'));
        $confirm->assertSessionHas('two_factor.recovery_codes_once');

        $staff->refresh();
        $this->assertTrue($staff->hasTwoFactorEnabled());
        $this->assertNotSame($secret, DB::table('users')->where('id', $staff->id)->value('two_factor_secret'));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $staff->id, 'action' => 'Two-factor authentication enabled']);
    }

    public function test_staff_password_login_stops_at_two_factor_challenge_until_valid_totp(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        [$secret] = $this->enableTwoFactor($staff);

        $login = $this->post(route('backpack.auth.login'), ['email' => $staff->email, 'password' => 'password']);
        $login->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest('web');
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee($staff->email);

        $verify = $this->post(route('two-factor.challenge.verify'), [
            'code' => app(TwoFactorAuthenticationService::class)->currentCode($secret),
        ]);
        $verify->assertRedirect(backpack_url('dashboard'));
        $this->assertAuthenticatedAs($staff, 'web');
    }

    public function test_totp_matches_standard_vector_and_an_accepted_step_cannot_be_replayed(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $this->assertSame('287082', $service->currentCode('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59));

        $staff = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$secret] = $this->enableTwoFactor($staff);
        $code = $service->currentCode($secret);

        $this->assertTrue($service->verifyAndConsume($staff, $code));
        $this->assertFalse($service->verifyAndConsume($staff->refresh(), $code));
    }

    public function test_recovery_code_logs_in_once_and_is_consumed(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [, $codes] = $this->enableTwoFactor($staff);
        $recoveryCode = $codes[0];

        $this->post(route('backpack.auth.login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->post(route('two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertRedirect(backpack_url('dashboard'));
        $this->assertAuthenticatedAs($staff, 'web');
        $this->assertCount(7, $staff->fresh()->two_factor_recovery_codes);

        backpack_auth()->logout();
        $this->post(route('backpack.auth.login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->post(route('two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertSessionHasErrors('code');
        $this->assertGuest('web');
    }

    public function test_hospital_login_uses_the_same_two_factor_challenge_and_returns_to_hospital_portal(): void
    {
        $hospital = Hospital::create(['code' => 'YGN-HSP-2FA001', 'name' => '2FA Hospital', 'region' => 'Yangon Region', 'is_active' => true]);
        $hospitalUser = User::factory()->create([
            'email' => 'two.factor.hospital@example.test',
            'role' => User::ROLE_HOSPITAL,
            'hospital_id' => $hospital->id,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        [$secret] = $this->enableTwoFactor($hospitalUser);

        $this->post(route('hospital.login.store'), ['email' => $hospitalUser->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest('web');

        $this->post(route('two-factor.challenge.verify'), [
            'code' => app(TwoFactorAuthenticationService::class)->currentCode($secret),
        ])->assertRedirect(route('hospital.dashboard'));
        $this->assertAuthenticatedAs($hospitalUser, 'web');
        $this->get(route('hospital.dashboard'))->assertOk()->assertSee(route('two-factor.manage'), false);
    }

    public function test_recovery_codes_can_be_regenerated_and_two_factor_can_be_disabled_with_step_up_checks(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_LAB_ADMIN]);
        [$secret, $oldCodes] = $this->enableTwoFactor($staff);
        backpack_auth()->login($staff);
        $service = app(TwoFactorAuthenticationService::class);

        $regenerate = $this->post(route('two-factor.recovery-codes.regenerate'), [
            'current_password' => 'password',
            'code' => $service->currentCode($secret),
        ]);
        $regenerate->assertRedirect(route('two-factor.recovery-codes'));
        $newCodes = session()->get('two_factor.recovery_codes_once');
        $this->assertIsArray($newCodes);
        $this->assertNotContains($oldCodes[0], $newCodes);

        $this->delete(route('two-factor.disable'), [
            'current_password' => 'password',
            'code' => $newCodes[0],
        ])->assertRedirect(route('two-factor.manage'));

        $staff->refresh();
        $this->assertFalse($staff->hasTwoFactorEnabled());
        $this->assertNull($staff->two_factor_secret);
        $this->assertNull($staff->two_factor_recovery_codes);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $staff->id, 'action' => 'Two-factor authentication disabled']);
    }

    /** @return array{0:string,1:array<int,string>} */
    private function enableTwoFactor(User $user): array
    {
        $service = app(TwoFactorAuthenticationService::class);
        $secret = $service->generateSecret();
        $codes = $service->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $service->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => null,
        ])->save();

        return [$secret, $codes];
    }
}
