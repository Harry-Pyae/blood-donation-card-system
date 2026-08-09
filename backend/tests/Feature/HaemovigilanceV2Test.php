<?php

namespace Tests\Feature;

use App\Models\AdverseReaction;
use App\Models\BloodAllocation;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HaemovigilanceV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_hospital_reaction_creates_reported_case_and_privacy_safe_staff_notifications(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $lab = User::factory()->create(['role' => User::ROLE_LAB_STAFF]);
        $labAdmin = User::factory()->create(['role' => User::ROLE_LAB_ADMIN]);
        [, $hospitalUser, $allocation] = $this->transfusionCase('HV-NOTIFY');

        $this->actingAs($hospitalUser)->post(route('hospital.reactions.store', $allocation), [
            'severity' => 'severe',
            'suspected_reaction_type' => 'febrile_non_hemolytic',
            'symptoms' => 'Fever and chills — private clinical detail.',
            'action_taken' => 'Transfusion stopped and clinical review started.',
            'occurred_at' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $reaction = AdverseReaction::firstOrFail();
        $this->assertSame('reported', $reaction->status);
        $this->assertSame('febrile_non_hemolytic', $reaction->suspected_reaction_type);

        foreach ([$admin, $staff, $lab, $labAdmin] as $recipient) {
            $notification = $recipient->notifications()->firstOrFail();
            $this->assertSame('reaction_reported', $notification->data['event']);
            $encoded = json_encode($notification->data);
            $this->assertStringNotContainsString('Fever and chills', $encoded);
            $this->assertStringNotContainsString('CASE-HV-NOTIFY', $encoded);
        }

        backpack_auth()->login($admin);
        $this->getJson(route('bloodcare.admin.notifications.unread-count'))
            ->assertOk()->assertJson(['count' => 1]);
        $this->patch(route('bloodcare.admin.notifications.open', $admin->notifications()->firstOrFail()->id))
            ->assertRedirect(route('bloodcare.admin.haemovigilance.show', $reaction));

        backpack_auth()->logout();
        backpack_auth()->login($lab);
        $this->getJson(route('bloodcare.lab.notifications.unread-count'))
            ->assertOk()->assertJson(['count' => 1]);
        $this->patch(route('bloodcare.lab.notifications.open', $lab->notifications()->firstOrFail()->id))
            ->assertRedirect(route('bloodcare.lab.inventory', ['unit' => $allocation->unit->unit_number]));
    }

    public function test_system_staff_can_investigate_case_and_hospital_receives_follow_up(): void
    {
        [$hospital, $hospitalUser, $allocation] = $this->transfusionCase('HV-REVIEW');
        $reaction = $this->reaction($hospital, $hospitalUser, $allocation, 'HVR-HV-REVIEW');
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        backpack_auth()->login($staff);

        $this->get(route('bloodcare.admin.haemovigilance'))
            ->assertOk()
            ->assertSee('HVR-HV-REVIEW')
            ->assertSee(__('bloodcare.national.haemovigilance.queue_title'));
        $this->get(route('bloodcare.admin.haemovigilance.show', $reaction))
            ->assertOk()
            ->assertSee('CASE-HV-REVIEW')
            ->assertSee(__('bloodcare.national.haemovigilance.symptoms'))
            ->assertSee('Private symptom detail');

        $this->patch(route('bloodcare.admin.haemovigilance.update', $reaction), [
            'status' => 'investigating',
            'reaction_type' => 'febrile_non_hemolytic',
            'imputability' => 'possible',
            'outcome' => 'recovered',
            'investigation_notes' => 'Identity, compatibility and timing reviewed.',
            'corrective_action' => 'Continue observation and complete review.',
        ])->assertRedirect();

        $reaction->refresh();
        $this->assertSame('investigating', $reaction->status);
        $this->assertSame($staff->id, $reaction->reviewed_by);
        $this->assertNotNull($reaction->reviewed_at);
        $this->assertSame('haemovigilance_updated', $hospitalUser->notifications()->firstOrFail()->data['event']);
    }

    public function test_closure_requires_complete_investigation_and_then_locks_case(): void
    {
        [$hospital, $hospitalUser, $allocation] = $this->transfusionCase('HV-CLOSE');
        $reaction = $this->reaction($hospital, $hospitalUser, $allocation, 'HVR-HV-CLOSE');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($admin);

        $this->from(route('bloodcare.admin.haemovigilance'))
            ->patch(route('bloodcare.admin.haemovigilance.update', $reaction), [
                'status' => 'closed',
                'reaction_type' => 'allergic',
                'imputability' => 'not_assessed',
            ])->assertRedirect(route('bloodcare.admin.haemovigilance'))
            ->assertSessionHasErrors('status');

        $payload = [
            'status' => 'closed',
            'reaction_type' => 'allergic',
            'imputability' => 'probable',
            'outcome' => 'recovered',
            'investigation_notes' => 'Trace-back and clinical review completed.',
            'corrective_action' => 'Clinical team advised and prevention plan documented.',
        ];
        $this->patch(route('bloodcare.admin.haemovigilance.update', $reaction), $payload)->assertRedirect();

        $reaction->refresh();
        $this->assertSame('closed', $reaction->status);
        $this->assertSame($admin->id, $reaction->closed_by);
        $this->assertNotNull($reaction->closed_at);

        $this->from(route('bloodcare.admin.haemovigilance'))
            ->patch(route('bloodcare.admin.haemovigilance.update', $reaction), [
                ...$payload,
                'reaction_type' => 'other',
            ])->assertRedirect(route('bloodcare.admin.haemovigilance'))
            ->assertSessionHasErrors('status');
        $this->assertSame('allergic', $reaction->fresh()->reaction_type);
    }

    public function test_hospital_isolation_and_inventory_trace_expose_only_safe_haemovigilance_metadata(): void
    {
        [$hospitalA, $userA, $allocationA] = $this->transfusionCase('HV-PRIVATE-A');
        [, $userB] = $this->transfusionCase('HV-PRIVATE-B');
        $reaction = $this->reaction($hospitalA, $userA, $allocationA, 'HVR-HV-PRIVATE-A');
        $reaction->update([
            'status' => 'investigating',
            'reaction_type' => 'febrile_non_hemolytic',
            'imputability' => 'possible',
            'outcome' => 'recovered',
            'reviewed_at' => now(),
            'investigation_notes' => 'RESTRICTED-INVESTIGATION-NOTE',
            'corrective_action' => 'RESTRICTED-CORRECTIVE-ACTION',
        ]);

        $this->actingAs($userB)->get(route('hospital.dashboard'))
            ->assertOk()
            ->assertDontSee('HVR-HV-PRIVATE-A')
            ->assertDontSee('CASE-HV-PRIVATE-A');

        auth()->logout();
        $lab = User::factory()->create(['role' => User::ROLE_LAB_STAFF]);
        backpack_auth()->login($lab);
        $this->get(route('bloodcare.admin.haemovigilance'))->assertForbidden();
        $response = $this->getJson(route('bloodcare.lab.inventory.details', ['unit' => $allocationA->unit->unit_number]))->assertOk();
        $response->assertJsonPath('haemovigilance.0.reference', 'HVR-HV-PRIVATE-A')
            ->assertJsonPath('haemovigilance.0.status', 'investigating');
        $encoded = json_encode($response->json());
        $this->assertStringNotContainsString('CASE-HV-PRIVATE-A', $encoded);
        $this->assertStringNotContainsString('Private symptom detail', $encoded);
        $this->assertStringNotContainsString('RESTRICTED-INVESTIGATION-NOTE', $encoded);
        $this->assertStringNotContainsString('RESTRICTED-CORRECTIVE-ACTION', $encoded);
    }

    /** @return array{0:Hospital,1:User,2:BloodAllocation} */
    private function transfusionCase(string $suffix): array
    {
        $hospital = Hospital::create(['code' => 'H-'.$suffix, 'name' => 'Hospital '.$suffix, 'is_active' => true]);
        $hospitalUser = User::factory()->create([
            'role' => User::ROLE_HOSPITAL,
            'hospital_id' => $hospital->id,
            'workplace' => $hospital->name,
        ]);
        $bloodRequest = BloodRequest::create([
            'reference' => 'REQ-'.$suffix,
            'hospital_id' => $hospital->id,
            'requested_by' => $hospitalUser->id,
            'patient_reference' => 'CASE-'.$suffix,
            'blood_group' => 'O+',
            'component_type' => 'red_cells',
            'quantity' => 1,
            'priority' => 'urgent',
            'status' => 'transfused',
        ]);
        $unit = BloodUnit::create([
            'unit_number' => 'BU-'.$suffix,
            'blood_group' => 'O+',
            'component_type' => 'red_cells',
            'collected_at' => today()->subDay(),
            'expires_at' => today()->addDays(20),
            'storage_location' => 'Cold room A',
            'status' => 'used',
            'released_at' => now()->subDay(),
        ]);
        $allocation = BloodAllocation::create([
            'blood_request_id' => $bloodRequest->id,
            'blood_unit_id' => $unit->id,
            'crossmatch_result' => 'compatible',
            'status' => 'transfused',
            'allocated_at' => now()->subHours(5),
            'dispatched_at' => now()->subHours(4),
            'received_at' => now()->subHours(3),
            'transfused_at' => now()->subHours(2),
        ]);

        return [$hospital, $hospitalUser, $allocation];
    }

    private function reaction(Hospital $hospital, User $hospitalUser, BloodAllocation $allocation, string $reference): AdverseReaction
    {
        return AdverseReaction::create([
            'reference' => $reference,
            'blood_allocation_id' => $allocation->id,
            'hospital_id' => $hospital->id,
            'reported_by' => $hospitalUser->id,
            'severity' => 'moderate',
            'symptoms' => 'Private symptom detail',
            'action_taken' => 'Clinical team reviewed the patient.',
            'occurred_at' => now()->subHour(),
            'status' => 'reported',
        ]);
    }
}
