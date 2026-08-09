<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackpackLocalAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backpack_login_uses_the_local_tabler_bundle(): void
    {
        foreach ([
            'vendor/bloodcare-backpack/css/tabler.min.css',
            'vendor/bloodcare-backpack/css/line-awesome.min.css',
            'vendor/bloodcare-backpack/js/jquery.min.js',
            'vendor/bloodcare-backpack/js/tabler.min.js',
        ] as $asset) {
            $this->assertFileExists(public_path($asset));
        }

        $response = $this->get(backpack_url('login'));

        $response->assertOk();
        $response->assertSee('vendor/bloodcare-backpack/css/tabler.min.css', false);
        $response->assertSee('vendor/bloodcare-backpack/css/line-awesome.min.css', false);
        $response->assertSee('vendor/bloodcare-backpack/js/jquery.min.js', false);
        $response->assertSee('vendor/bloodcare-backpack/js/tabler.min.js', false);
        $response->assertDontSee('cdn.jsdelivr.net/npm/@tabler/core', false);
    }

    public function test_approved_staff_dashboard_uses_the_same_local_bundle(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);

        $this->actingAs($staff, 'web');

        $response = $this->get(backpack_url('dashboard'));

        $response->assertOk();
        $this->assertSame('bloodCareAvatarUrl', config('backpack.base.avatar_type'));
        $this->assertFileExists(public_path('images/bloodcare-staff-avatar.svg'));
        $this->assertStringEndsWith(
            '/images/bloodcare-staff-avatar.svg',
            backpack_avatar_url($staff),
        );
        $response->assertSee('vendor/bloodcare-backpack/css/tabler.min.css', false);
        $response->assertSee('vendor/bloodcare-backpack/js/tabler.min.js', false);
        $response->assertSee('images/bloodcare-staff-avatar.svg', false);
        $this->assertSame([], config('backpack.theme-tabler.styles'));
        $response->assertDontSee('backpack-color-palette.css', false);
        $response->assertDontSee('glass.css', false);
        $response->assertDontSee('fuzzy-background.css', false);
        $response->assertDontSee('gravatar.com', false);
        $response->assertDontSee(backpack_url('users'), false);
    }

    public function test_myanmar_dashboard_uses_compact_public_style_preference_controls(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'is_banned' => false,
        ]);

        $this->actingAs($staff, 'web');

        $response = $this
            ->withSession(['locale' => 'my'])
            ->get(backpack_url('dashboard'));

        $response->assertOk();
        $response->assertSee('lang="my"', false);
        $response->assertSee('data-bc-theme-toggle', false);
        $response->assertSee('data-bc-language-menu', false);
        $response->assertSee('မြန်မာ', false);
        $response->assertDontSee('bc-preference-group', false);

        $styles = file_get_contents(public_path('css/bloodcare-admin.css'));
        $this->assertIsString($styles);
        $this->assertStringContainsString('html[lang="my"] body :not(.la):not(.las):not(.lar):not(.lab)', $styles);
        $this->assertStringContainsString('html[lang="my"] .bc-filter-dropdown-trigger > [data-bc-select-label]', $styles);
        $this->assertStringContainsString('white-space: normal;', $styles);
    }

    public function test_admin_tables_include_the_shared_mobile_card_adapter(): void
    {
        $script = file_get_contents(public_path('js/bloodcare-admin.js'));
        $styles = file_get_contents(public_path('css/bloodcare-admin.css'));

        $this->assertIsString($script);
        $this->assertIsString($styles);
        $this->assertStringContainsString('initializeResponsiveTables', $script);
        $this->assertStringContainsString("cell.setAttribute('data-label', labels[index])", $script);
        $this->assertStringContainsString('table.bc-responsive-table tbody td::before', $styles);
        $this->assertStringContainsString('@media (max-width: 700px)', $styles);
    }
}
