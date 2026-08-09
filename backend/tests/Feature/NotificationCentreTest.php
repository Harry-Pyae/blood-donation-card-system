<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\Hospital;
use App\Models\User;
use App\Notifications\BloodCareWorkflowNotification;
use App\Services\BloodCareNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_and_laboratory_notification_centres_are_personal_and_role_safe(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lab = User::factory()->create(['role' => User::ROLE_LAB_STAFF]);

        $admin->notify(new BloodCareWorkflowNotification('unit_released', 'success', [
            'unit' => 'SYS-ONLY', 'group' => 'O+', 'donation' => 'DON-SYS',
        ], 'bloodcare.admin.inventory'));
        $lab->notify(new BloodCareWorkflowNotification('unit_quarantined', 'warning', [
            'unit' => 'LAB-ONLY', 'group' => 'A+', 'donation' => 'DON-LAB',
        ], 'bloodcare.lab.laboratory'));

        backpack_auth()->login($admin);
        $this->getJson(route('bloodcare.admin.notifications.unread-count'))
            ->assertOk()->assertJson(['count' => 1]);
        $this->get(route('bloodcare.admin.notifications'))
            ->assertOk()
            ->assertSee('SYS-ONLY')
            ->assertDontSee('LAB-ONLY');
        $this->get(route('bloodcare.lab.notifications'))->assertForbidden();

        $adminNotification = $admin->notifications()->firstOrFail();
        $labNotification = $lab->notifications()->firstOrFail();
        $this->patch(route('bloodcare.admin.notifications.open', $adminNotification->id))
            ->assertRedirect(route('bloodcare.admin.inventory', ['unit' => 'SYS-ONLY']));
        $this->assertNotNull($adminNotification->fresh()->read_at);
        $this->patch(route('bloodcare.admin.notifications.read', $labNotification->id))->assertNotFound();

        backpack_auth()->logout();
        backpack_auth()->login($lab);
        $this->getJson(route('bloodcare.lab.notifications.unread-count'))
            ->assertOk()->assertJson(['count' => 1]);
        $this->get(route('bloodcare.lab.notifications'))
            ->assertOk()
            ->assertSee('LAB-ONLY')
            ->assertDontSee('SYS-ONLY');
        $this->patch(route('bloodcare.lab.notifications.open', $labNotification->id))
            ->assertRedirect(route('bloodcare.lab.laboratory', ['q' => 'LAB-ONLY']));
        $this->assertNotNull($labNotification->fresh()->read_at);
        $this->get(route('bloodcare.admin.notifications'))->assertForbidden();
    }

    public function test_workflow_notifications_cross_the_system_and_laboratory_boundary_without_donor_identity(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $lab = User::factory()->create(['role' => User::ROLE_LAB_STAFF]);
        $notifications = app(BloodCareNotificationService::class);

        $donation = new Donation(['reference' => 'DON-NOTIFY-001']);
        $unit = new BloodUnit(['unit_number' => 'BU-NOTIFY-001', 'blood_group' => 'O+']);

        $notifications->donationQuarantined($donation, $unit);
        $this->assertSame('unit_quarantined', $lab->notifications()->firstOrFail()->data['event']);
        $this->assertSame(0, $admin->notifications()->count());
        $this->assertSame(0, $staff->notifications()->count());
        $this->assertStringNotContainsString('donor', strtolower(json_encode($lab->notifications()->firstOrFail()->data)));

        $notifications->laboratoryDecision($donation, $unit, 'released');
        $this->assertSame('unit_released', $admin->notifications()->firstOrFail()->data['event']);
        $this->assertSame('unit_released', $staff->notifications()->firstOrFail()->data['event']);
        $this->assertSame(1, $lab->notifications()->count());
    }

    public function test_hospital_request_updates_notify_staff_then_return_to_the_requesting_hospital(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $hospital = Hospital::create(['code' => 'YGN-HSP-NOTIFY', 'name' => 'Notification Hospital', 'is_active' => true]);
        $hospitalUser = User::factory()->create([
            'role' => User::ROLE_HOSPITAL,
            'hospital_id' => $hospital->id,
        ]);

        $this->actingAs($hospitalUser)->post(route('hospital.requests.store'), [
            'patient_reference' => 'CASE-NOTIFY-001',
            'blood_group' => 'O+',
            'component_type' => 'red_cells',
            'quantity' => 1,
            'priority' => 'urgent',
        ])->assertRedirect();

        $request = BloodRequest::where('hospital_id', $hospital->id)->firstOrFail();
        $this->assertSame('hospital_request', $staff->notifications()->firstOrFail()->data['event']);

        auth()->logout();
        backpack_auth()->login($staff);
        $this->patch(route('bloodcare.admin.blood-requests.review', $request), [
            'decision' => 'approve',
        ])->assertRedirect();

        $hospitalNotification = $hospitalUser->notifications()->firstOrFail();
        $this->assertSame('request_approved', $hospitalNotification->data['event']);

        backpack_auth()->logout();
        $this->actingAs($hospitalUser)
            ->get(route('hospital.dashboard'))
            ->assertOk()
            ->assertSee(__('bloodcare.notifications.events.request_approved.title'));
        $this->patch(route('hospital.notifications.read', $hospitalNotification->id))->assertRedirect();
        $this->assertNotNull($hospitalNotification->fresh()->read_at);
    }
}
