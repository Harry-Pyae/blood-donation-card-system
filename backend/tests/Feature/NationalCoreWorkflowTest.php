<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\BloodAllocation;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalCoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_release_requires_mandatory_negative_results_and_hides_donor_identity(): void
    {
        $lab=User::factory()->create(['role'=>User::ROLE_LAB_STAFF]);
        [$donor,$donation,$unit]=$this->collectedDonation();
        backpack_auth()->login($lab);

        $this->get(route('bloodcare.lab.laboratory'))->assertOk()->assertSee($donation->reference)->assertDontSee($donor->full_name);
        $payload = $this->negativeLabPayload();
        $this->post(route('bloodcare.lab.laboratory.store',$donation),$payload)->assertRedirect();

        $this->assertDatabaseHas('lab_tests',['donation_id'=>$donation->id,'release_status'=>'released']);
        $this->assertDatabaseHas('blood_units',['id'=>$unit->id,'status'=>'available']);
        $labTestId = LabTest::where('donation_id', $donation->id)->sole()->id;

        // A repeated browser submit of the exact finalized decision is idempotent.
        $this->post(route('bloodcare.lab.laboratory.store',$donation),$payload)->assertRedirect();
        $this->assertSame(1, LabTest::where('donation_id', $donation->id)->count());
        $this->assertSame($labTestId, LabTest::where('donation_id', $donation->id)->sole()->id);

        // The safety lock still rejects an attempt to alter finalized results.
        $changedPayload = $payload;
        $changedPayload['hiv_status'] = 'reactive';
        $this->post(route('bloodcare.lab.laboratory.store',$donation),$changedPayload)->assertStatus(422);
        $this->assertDatabaseHas('lab_tests',['id'=>$labTestId,'hiv_status'=>'negative','release_status'=>'released']);
        $this->get(route('bloodcare.lab.laboratory'))
            ->assertOk()
            ->assertSee(__('bloodcare.national.laboratory.details_title'))
            ->assertSee(__('bloodcare.national.laboratory.tested_by'))
            ->assertSee($lab->name)
            ->assertDontSee($donor->full_name);
    }

    public function test_reactive_lab_result_discards_unit_instead_of_releasing_it(): void
    {
        $lab=User::factory()->create(['role'=>User::ROLE_LAB_ADMIN]); [, $donation,$unit]=$this->collectedDonation(); backpack_auth()->login($lab);
        $payload=$this->negativeLabPayload(); $payload['hiv_status']='reactive';
        $this->post(route('bloodcare.lab.laboratory.store',$donation),$payload)->assertRedirect();
        $this->assertDatabaseHas('lab_tests',['donation_id'=>$donation->id,'release_status'=>'discarded']);
        $this->assertDatabaseHas('blood_units',['id'=>$unit->id,'status'=>'discarded','released_at'=>null]);
        $this->get(route('bloodcare.lab.laboratory'))
            ->assertOk()
            ->assertSee(__('bloodcare.national.laboratory.discard_reason'))
            ->assertSee(__('bloodcare.national.laboratory.discard_reactive', ['tests' => 'HIV']));
    }

    public function test_laboratory_quarantine_queue_uses_the_actual_blood_unit_lifecycle_state(): void
    {
        $lab=User::factory()->create(['role'=>User::ROLE_LAB_STAFF]);
        [, $quarantinedDonation]=$this->collectedDonation();
        [, $historicalDonation, $historicalUnit]=$this->collectedDonation();
        $historicalUnit->update(['status'=>'used']);
        backpack_auth()->login($lab);

        $this->get(route('bloodcare.lab.laboratory', ['safety'=>'quarantined']))
            ->assertOk()
            ->assertSee($quarantinedDonation->reference)
            ->assertDontSee($historicalDonation->reference);

        $dashboard=$this->get(route('bloodcare.lab.dashboard'))->assertOk();
        $this->assertSame(1, $dashboard->viewData('pendingTests'));

        // A stale/direct POST to a historical non-quarantined unit returns to
        // the Laboratory UI with a validation message instead of a 400 page.
        $this->from(route('bloodcare.lab.laboratory', ['safety'=>'quarantined']))
            ->post(route('bloodcare.lab.laboratory.store', $historicalDonation), $this->negativeLabPayload())
            ->assertRedirect(route('bloodcare.lab.laboratory', ['safety'=>'quarantined']))
            ->assertSessionHasErrors('laboratory');

        $this->assertDatabaseMissing('lab_tests', ['donation_id'=>$historicalDonation->id]);
        $this->assertSame('used', $historicalUnit->fresh()->status);
    }

    public function test_immunohematology_and_regional_tti_results_are_part_of_the_release_gate(): void
    {
        $lab=User::factory()->create(['role'=>User::ROLE_LAB_ADMIN]);
        [, $donation, $unit]=$this->collectedDonation();
        backpack_auth()->login($lab);

        $payload=$this->negativeLabPayload();
        $payload['antibody_screen_status']='reactive';
        $this->post(route('bloodcare.lab.laboratory.store',$donation),$payload)->assertRedirect();

        $this->assertDatabaseHas('lab_tests',[
            'donation_id'=>$donation->id,
            'rhd_type'=>'positive',
            'antibody_screen_status'=>'reactive',
            'htlv_status'=>'not_required',
            'malaria_status'=>'not_required',
            'chagas_status'=>'not_required',
            'west_nile_status'=>'not_required',
            'zika_status'=>'not_required',
            'release_status'=>'discarded',
        ]);
        $this->assertDatabaseHas('blood_units',['id'=>$unit->id,'status'=>'discarded','released_at'=>null]);
    }

    public function test_released_whole_blood_can_be_processed_into_independently_tracked_components(): void
    {
        $staff=User::factory()->create(['role'=>User::ROLE_LAB_STAFF]); [,,$unit]=$this->collectedDonation();
        $unit->update(['status'=>'available','released_at'=>now(),'released_by'=>$staff->id]); backpack_auth()->login($staff);

        $this->post(route('bloodcare.lab.components.store',$unit),[
            'components'=>['red_cells'],
            'modifiers'=>['red_cells'=>['leukoreduced'=>1,'irradiated'=>1,'washed'=>1]],
            'location'=>'Component room A',
        ])->assertRedirect();
        $this->assertSame(1,$unit->components()->count());
        $this->assertSame('processing',$unit->fresh()->status);
        $this->assertDatabaseHas('blood_units',[
            'parent_blood_unit_id'=>$unit->id,
            'component_type'=>'red_cells',
            'leukoreduced'=>1,
            'irradiated'=>1,
            'washed'=>1,
        ]);
        $this->get(route('bloodcare.lab.components', ['processing' => 'partial']))
            ->assertOk()
            ->assertSee($unit->unit_number)
            ->assertSee(__('bloodcare.national.components.partially_processed'))
            ->assertSee(__('bloodcare.national.components.process_components'));

        $this->post(route('bloodcare.lab.components.store',$unit),['components'=>['plasma','platelets'],'location'=>'Component room A'])->assertRedirect();
        $this->assertSame(3,$unit->components()->count());
        $this->assertSame('used',$unit->fresh()->status);
        $this->assertDatabaseHas('blood_units',['parent_blood_unit_id'=>$unit->id,'component_type'=>'platelets','status'=>'available']);

        $plasma=$unit->components()->where('component_type','plasma')->firstOrFail();
        $this->post(route('bloodcare.lab.components.store',$unit),['components'=>['cryoprecipitate'],'location'=>'Component room A'])->assertRedirect();
        $this->assertDatabaseHas('blood_units',[
            'parent_blood_unit_id'=>$plasma->id,
            'component_type'=>'cryoprecipitate',
            'status'=>'available',
        ]);
        $this->assertSame('used',$plasma->fresh()->status);
        $this->assertSame('used',$unit->fresh()->status);
        $this->assertSame(3,$unit->components()->count());
        $this->get(route('bloodcare.lab.components'))
            ->assertOk()
            ->assertSee($unit->unit_number)
            ->assertSee(__('bloodcare.national.components.process_components'))
            ->assertSee('bc-component-process-button is-disabled', false)
            ->assertSee(__('bloodcare.national.components.fully_processed'));
    }

    public function test_hospital_portal_isolates_each_hospitals_requests(): void
    {
        [$hospitalA,$userA]=$this->hospitalAccount('H-A','Hospital A','ha@example.test');
        [$hospitalB,$userB]=$this->hospitalAccount('H-B','Hospital B','hb@example.test');
        BloodRequest::create(['reference'=>'REQ-A','hospital_id'=>$hospitalA->id,'requested_by'=>$userA->id,'patient_reference'=>'CASE-A','blood_group'=>'O+','component_type'=>'red_cells','quantity'=>1,'priority'=>'routine','status'=>'pending']);
        BloodRequest::create(['reference'=>'REQ-B','hospital_id'=>$hospitalB->id,'requested_by'=>$userB->id,'patient_reference'=>'CASE-B-SECRET','blood_group'=>'A+','component_type'=>'plasma','quantity'=>1,'priority'=>'urgent','status'=>'pending']);
        $this->actingAs($userA)->get(route('hospital.dashboard'))->assertOk()->assertSee('REQ-A')->assertDontSee('REQ-B')->assertDontSee('CASE-B-SECRET');
    }

    public function test_public_site_exposes_the_hospital_portal_entry(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('hospital.login'), false)
            ->assertSee(__('bloodcare.public.nav.hospital_portal'));

        $this->get(route('hospital.login'))
            ->assertOk()
            ->assertSee(__('bloodcare.national.portal.hospital_portal'));
    }

    public function test_hospital_request_flows_to_staff_review_and_status_returns_to_hospital(): void
    {
        [$hospital, $hospitalUser] = $this->hospitalAccount('H-FLOW', 'Flow Hospital', 'flow@example.test');

        $this->actingAs($hospitalUser)->post(route('hospital.requests.store'), [
            'patient_reference' => 'CASE-FLOW-001',
            'blood_group' => 'O+',
            'component_type' => 'red_cells',
            'quantity' => 2,
            'priority' => 'urgent',
            'clinical_note' => 'Cross-match requested by treating team.',
        ])->assertRedirect();

        $bloodRequest = BloodRequest::query()
            ->where('hospital_id', $hospital->id)
            ->where('patient_reference', 'CASE-FLOW-001')
            ->firstOrFail();

        $this->assertSame('pending', $bloodRequest->status);

        auth()->logout();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($admin);

        $this->get(route('bloodcare.admin.blood-requests'))
            ->assertOk()
            ->assertSee($bloodRequest->reference)
            ->assertSee('Flow Hospital')
            ->assertSee('CASE-FLOW-001');

        $this->patch(route('bloodcare.admin.blood-requests.review', $bloodRequest), [
            'decision' => 'approve',
        ])->assertRedirect();

        $this->assertSame('approved', $bloodRequest->fresh()->status);

        backpack_auth()->logout();
        $this->actingAs($hospitalUser)
            ->get(route('hospital.dashboard'))
            ->assertOk()
            ->assertSee($bloodRequest->reference)
            ->assertSee(__('bloodcare.national.common.approved'));
    }

    public function test_hospital_accounts_generate_unique_codes_and_can_be_searched(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($admin);

        $this->post(route('bloodcare.admin.hospitals.store'), [
            'code' => 'MANUAL-CODE-MUST-BE-IGNORED',
            'name' => 'Searchable General Hospital',
            'region' => 'Yangon Region',
            'address' => 'Bogyoke Road, Yangon',
            'phone' => '09 111 222 333',
            'contact_name' => 'Dr Search Contact',
            'email' => 'search.hospital@example.test',
            'password' => 'Hospital123!',
        ])->assertRedirect();

        $this->post(route('bloodcare.admin.hospitals.store'), [
            'name' => 'Hidden Mandalay Hospital',
            'region' => 'Mandalay Region',
            'contact_name' => 'Dr Hidden Contact',
            'email' => 'hidden.hospital@example.test',
            'password' => 'Hospital123!',
        ])->assertRedirect();

        $searchable = Hospital::query()->where('name', 'Searchable General Hospital')->firstOrFail();
        $hidden = Hospital::query()->where('name', 'Hidden Mandalay Hospital')->firstOrFail();

        $this->assertMatchesRegularExpression('/^YGN-HSP-[A-Z0-9]{6}$/', $searchable->code);
        $this->assertMatchesRegularExpression('/^MDY-HSP-[A-Z0-9]{6}$/', $hidden->code);
        $this->assertNotSame($searchable->code, $hidden->code);
        $this->assertNotSame('MANUAL-CODE-MUST-BE-IGNORED', $searchable->code);

        $this->post(route('bloodcare.admin.hospitals.store'), [
            'name' => 'Invalid State Hospital',
            'region' => 'Shan State',
            'contact_name' => 'Invalid Contact',
            'email' => 'invalid.state@example.test',
            'password' => 'Hospital123!',
        ])->assertSessionHasErrors('region');
        $this->assertDatabaseMissing('hospitals', ['name' => 'Invalid State Hospital']);

        $this->get(route('bloodcare.admin.hospitals', ['q' => 'Searchable General']))
            ->assertOk()
            ->assertSee('Searchable General Hospital')
            ->assertSee($searchable->code)
            ->assertSee('data-value="Ayeyarwady Region"', false)
            ->assertSee('data-value="Bago Region"', false)
            ->assertSee('data-value="Magway Region"', false)
            ->assertSee('data-value="Mandalay Region"', false)
            ->assertSee('data-value="Sagaing Region"', false)
            ->assertSee('data-value="Tanintharyi Region"', false)
            ->assertSee('data-value="Yangon Region"', false)
            ->assertDontSee('Hidden Mandalay Hospital');
    }

    public function test_hospital_accounts_support_detail_edit_and_traceability_safe_delete(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($admin);

        $hospital = Hospital::create([
            'code' => 'YGN-HSP-ABC123',
            'name' => 'Editable Hospital',
            'region' => 'Yangon Region',
            'address' => 'Old address',
            'phone' => '09 100 200 300',
            'is_active' => true,
        ]);
        $portalUser = User::factory()->create([
            'role' => User::ROLE_HOSPITAL,
            'hospital_id' => $hospital->id,
            'workplace' => $hospital->name,
        ]);

        $this->get(route('bloodcare.admin.hospitals'))
            ->assertOk()
            ->assertSee('data-bc-hospital-open="details-'.$hospital->id.'"', false)
            ->assertSee('data-bc-hospital-open="edit-'.$hospital->id.'"', false)
            ->assertSee('data-bc-hospital-open="delete-'.$hospital->id.'"', false);

        $this->patch(route('bloodcare.admin.hospitals.update', $hospital), [
            'name' => 'Bago Regional Hospital',
            'region' => 'Bago Region',
            'address' => 'New Bago address',
            'phone' => '09 700 800 900',
            'is_active' => '1',
        ])->assertRedirect();

        $hospital->refresh();
        $this->assertSame('Bago Regional Hospital', $hospital->name);
        $this->assertSame('Bago Region', $hospital->region);
        $this->assertMatchesRegularExpression('/^BGO-HSP-[A-Z0-9]{6}$/', $hospital->code);
        $this->assertSame('Bago Regional Hospital', $portalUser->fresh()->workplace);

        $this->delete(route('bloodcare.admin.hospitals.destroy', $hospital))->assertRedirect();
        $this->assertDatabaseMissing('hospitals', ['id' => $hospital->id]);
        $this->assertDatabaseHas('users', ['id' => $portalUser->id, 'hospital_id' => null, 'is_banned' => true]);

        [$protectedHospital, $protectedUser] = $this->hospitalAccount('YGN-HSP-TRACE1', 'Traceability Hospital', 'traceability@example.test');
        BloodRequest::create([
            'reference' => 'REQ-TRACE-HOSPITAL',
            'hospital_id' => $protectedHospital->id,
            'requested_by' => $protectedUser->id,
            'patient_reference' => 'CASE-TRACE',
            'blood_group' => 'O+',
            'component_type' => 'red_cells',
            'quantity' => 1,
            'priority' => 'routine',
            'status' => 'pending',
        ]);

        $this->delete(route('bloodcare.admin.hospitals.destroy', $protectedHospital))
            ->assertSessionHasErrors('hospital');
        $this->assertDatabaseHas('hospitals', ['id' => $protectedHospital->id]);
    }

    public function test_blood_requests_are_searchable_and_expose_complete_scrollable_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        backpack_auth()->login($admin);

        $hospital = Hospital::create(['code' => 'H-DETAIL', 'name' => 'Detail Hospital', 'phone' => '09 555 111 222', 'is_active' => true]);
        $hospitalUser = User::factory()->create([
            'name' => 'Portal Contact',
            'email' => 'portal.detail@example.test',
            'phone' => '09 555 333 444',
            'role' => User::ROLE_HOSPITAL,
            'hospital_id' => $hospital->id,
        ]);
        $request = BloodRequest::create([
            'reference' => 'REQ-DETAIL-001',
            'hospital_id' => $hospital->id,
            'requested_by' => $hospitalUser->id,
            'patient_reference' => 'CASE-DETAIL-77',
            'blood_group' => 'O-',
            'component_type' => 'red_cells',
            'quantity' => 3,
            'priority' => 'emergency',
            'status' => 'pending',
            'clinical_note' => 'Emergency operating theatre request.',
        ]);

        [$otherHospital, $otherUser] = $this->hospitalAccount('H-HIDDEN', 'Unrelated Hospital', 'unrelated@example.test');
        BloodRequest::create([
            'reference' => 'REQ-HIDDEN-001',
            'hospital_id' => $otherHospital->id,
            'requested_by' => $otherUser->id,
            'patient_reference' => 'CASE-HIDDEN-SECRET',
            'blood_group' => 'A+',
            'component_type' => 'plasma',
            'quantity' => 1,
            'priority' => 'routine',
            'status' => 'pending',
        ]);

        $this->get(route('bloodcare.admin.blood-requests', ['q' => 'REQ-DETAIL-001']))
            ->assertOk()
            ->assertSee('Detail Hospital')
            ->assertSee('CASE-DETAIL-77')
            ->assertSee('REQ-DETAIL-001')
            ->assertSee('09 555 111 222')
            ->assertSee('09 555 333 444')
            ->assertSee('Emergency operating theatre request.')
            ->assertSee('data-bc-request-open="'.$request->id.'"', false)
            ->assertSee('bc-request-view-button', false)
            ->assertSee('bc-priority bc-priority-emergency', false)
            ->assertSee('data-bc-request-reject="'.$request->id.'"', false)
            ->assertSee('bc-request-approve-form', false)
            ->assertSee('bc-request-reject-detail', false)
            ->assertSee(__('bloodcare.national.requests.reject_reason_placeholder'))
            ->assertDontSee('CASE-HIDDEN-SECRET');
    }

    public function test_lab_role_cannot_open_donor_management_by_typing_the_url(): void
    {
        $lab=User::factory()->create(['role'=>User::ROLE_LAB_STAFF]); backpack_auth()->login($lab);
        $this->get(route('bloodcare.admin.donors'))->assertForbidden();
        $this->get(route('bloodcare.lab.laboratory'))->assertOk();
    }

    public function test_hospital_cannot_receive_another_hospitals_allocation(): void
    {
        [$hospitalA,$userA]=$this->hospitalAccount('H-ISO-A','Isolation A','iso-a@example.test');
        [$hospitalB,$userB]=$this->hospitalAccount('H-ISO-B','Isolation B','iso-b@example.test');
        $requestB=BloodRequest::create(['reference'=>'REQ-ISO-B','hospital_id'=>$hospitalB->id,'requested_by'=>$userB->id,'patient_reference'=>'CASE-ISO-B','blood_group'=>'O+','component_type'=>'whole_blood','quantity'=>1,'priority'=>'routine','status'=>'dispatched']);
        [,,$unit]=$this->collectedDonation(); $unit->update(['status'=>'used','released_at'=>now()]);
        $allocation=BloodAllocation::create(['blood_request_id'=>$requestB->id,'blood_unit_id'=>$unit->id,'crossmatch_result'=>'compatible','status'=>'dispatched','allocated_at'=>now(),'dispatched_at'=>now()]);
        $this->actingAs($userA)->patch(route('hospital.allocations.receive',$allocation))->assertNotFound();
        $this->assertSame('dispatched',$allocation->fresh()->status);
    }

    public function test_quarantined_or_unreleased_stock_cannot_be_allocated_to_hospital(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_ADMIN]); [$hospital,$hospitalUser]=$this->hospitalAccount('H-C','Hospital C','hc@example.test');
        [,,$unit]=$this->collectedDonation();
        $request=BloodRequest::create(['reference'=>'REQ-C','hospital_id'=>$hospital->id,'requested_by'=>$hospitalUser->id,'patient_reference'=>'CASE-C','blood_group'=>$unit->blood_group,'component_type'=>'whole_blood','quantity'=>1,'priority'=>'emergency','status'=>'approved']);
        backpack_auth()->login($admin);
        $this->post(route('bloodcare.admin.blood-requests.allocate',$request),['unit_number'=>$unit->unit_number,'crossmatch_result'=>'compatible'])->assertSessionHasErrors('unit_number');
        $this->assertDatabaseCount('blood_allocations',0); $this->assertSame('quarantined',$unit->fresh()->status);
    }

    public function test_fefo_recommends_earliest_expiring_released_matching_unit(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_ADMIN]);
        [$hospital,$hospitalUser]=$this->hospitalAccount('H-FEFO','FEFO Hospital','fefo@example.test');
        $request=BloodRequest::create(['reference'=>'REQ-FEFO','hospital_id'=>$hospital->id,'requested_by'=>$hospitalUser->id,'patient_reference'=>'CASE-FEFO','blood_group'=>'O+','component_type'=>'whole_blood','quantity'=>1,'priority'=>'routine','status'=>'approved']);

        [,,$later]=$this->collectedDonation();
        $later->update(['unit_number'=>'BU-FEFO-LATER','status'=>'available','released_at'=>now(),'released_by'=>$admin->id,'expires_at'=>today()->addDays(20),'storage_location'=>'Cold room B']);
        [,,$earlier]=$this->collectedDonation();
        $earlier->update(['unit_number'=>'BU-FEFO-FIRST','status'=>'available','released_at'=>now(),'released_by'=>$admin->id,'expires_at'=>today()->addDays(5),'storage_location'=>'Cold room A']);
        [,,$unreleased]=$this->collectedDonation();
        $unreleased->update(['unit_number'=>'BU-FEFO-UNSAFE','expires_at'=>today()->addDay()]);

        backpack_auth()->login($admin);
        $response=$this->get(route('bloodcare.admin.blood-requests',['q'=>'REQ-FEFO']))->assertOk();
        $response->assertSee('data-bc-fefo-unit="BU-FEFO-FIRST"',false)
            ->assertSee('id="bc-unit-menu-'.$request->id.'"',false)
            ->assertSee('data-value="BU-FEFO-FIRST"',false)
            ->assertSee('data-value="BU-FEFO-LATER"',false)
            ->assertSee('data-value="compatible"',false)
            ->assertSee('data-value="incompatible"',false)
            ->assertSeeInOrder(['BU-FEFO-FIRST','BU-FEFO-LATER'])
            ->assertDontSee('BU-FEFO-UNSAFE');
    }

    public function test_hospital_modifier_requirements_filter_fefo_and_block_mismatched_allocation(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_ADMIN]);
        [$hospital,$hospitalUser]=$this->hospitalAccount('H-MOD','Modifier Hospital','modifier@example.test');
        $request=BloodRequest::create([
            'reference'=>'REQ-MODIFIER',
            'hospital_id'=>$hospital->id,
            'requested_by'=>$hospitalUser->id,
            'patient_reference'=>'CASE-MODIFIER',
            'blood_group'=>'O+',
            'component_type'=>'red_cells',
            'requires_leukoreduced'=>true,
            'requires_washed'=>true,
            'quantity'=>1,
            'priority'=>'routine',
            'status'=>'approved',
        ]);
        $unmodified=BloodUnit::create([
            'unit_number'=>'BU-MOD-UNMODIFIED',
            'blood_group'=>'O+',
            'component_type'=>'red_cells',
            'collected_at'=>today()->subDays(2),
            'expires_at'=>today()->addDays(5),
            'storage_location'=>'Cold room A',
            'status'=>'available',
            'released_at'=>now()->subDay(),
        ]);
        $modified=BloodUnit::create([
            'unit_number'=>'BU-MOD-MATCH',
            'blood_group'=>'O+',
            'component_type'=>'red_cells',
            'leukoreduced'=>true,
            'washed'=>true,
            'collected_at'=>today()->subDays(2),
            'expires_at'=>today()->addDays(10),
            'storage_location'=>'Cold room A',
            'status'=>'available',
            'released_at'=>now()->subDay(),
        ]);

        backpack_auth()->login($admin);
        $this->get(route('bloodcare.admin.blood-requests',['q'=>'REQ-MODIFIER']))
            ->assertOk()
            ->assertSee('BU-MOD-MATCH')
            ->assertDontSee('BU-MOD-UNMODIFIED');

        $this->post(route('bloodcare.admin.blood-requests.allocate',$request),[
            'unit_number'=>$unmodified->unit_number,
            'crossmatch_result'=>'compatible',
        ])->assertSessionHasErrors('unit_number');
        $this->assertDatabaseCount('blood_allocations',0);

        $this->post(route('bloodcare.admin.blood-requests.allocate',$request),[
            'unit_number'=>$modified->unit_number,
            'crossmatch_result'=>'compatible',
        ])->assertRedirect();
        $this->assertDatabaseHas('blood_allocations',['blood_request_id'=>$request->id,'blood_unit_id'=>$modified->id,'status'=>'allocated']);
        $this->assertSame('reserved',$modified->fresh()->status);
    }

    private function collectedDonation(): array
    {
        $donor=Donor::create(['reference'=>'BC-'.uniqid(),'full_name'=>'Private Donor '.uniqid(),'date_of_birth'=>'1995-01-01','gender'=>'female','identity_document_type'=>'passport','identity_number'=>'P'.uniqid(),'passport_number'=>'P'.uniqid(),'phone'=>'09123456789','phone_normalized'=>'09123456789','address'=>'Yangon','blood_group'=>'O+','emergency_contact'=>'09111111111','consent_at'=>now(),'status'=>'active','eligibility_status'=>'deferred']);
        $donation=Donation::create(['reference'=>'DON-'.uniqid(),'donor_id'=>$donor->id,'donation_date'=>today(),'quantity_ml'=>450,'blood_group'=>'O+','donation_type'=>'whole_blood','screening_result'=>'passed','status'=>'accepted','bag_unit_number'=>'BU-'.uniqid(),'expires_at'=>today()->addDays(42),'storage_location'=>'Cold room A']);
        $unit=BloodUnit::create(['unit_number'=>$donation->bag_unit_number,'donation_id'=>$donation->id,'blood_group'=>'O+','component_type'=>'whole_blood','collected_at'=>today(),'expires_at'=>today()->addDays(42),'storage_location'=>'Cold room A','status'=>'quarantined']);
        return [$donor,$donation,$unit];
    }

    private function negativeLabPayload(): array
    {
        return [
            'hiv_status'=>'negative',
            'hepatitis_b_status'=>'negative',
            'hepatitis_c_status'=>'negative',
            'syphilis_status'=>'negative',
            'confirmed_blood_group'=>'O+',
            'rhd_type'=>'positive',
            'antibody_screen_status'=>'negative',
            'htlv_status'=>'not_required',
            'malaria_status'=>'not_required',
            'chagas_status'=>'not_required',
            'west_nile_status'=>'not_required',
            'zika_status'=>'not_required',
            'notes'=>'Mandatory screen complete.',
        ];
    }

    private function hospitalAccount(string $code,string $name,string $email): array
    {
        $hospital=Hospital::create(['code'=>$code,'name'=>$name,'is_active'=>true]);
        $user=User::factory()->create(['email'=>$email,'role'=>User::ROLE_HOSPITAL,'hospital_id'=>$hospital->id]);
        return [$hospital,$user];
    }
}
