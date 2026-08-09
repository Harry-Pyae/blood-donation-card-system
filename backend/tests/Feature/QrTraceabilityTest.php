<?php

namespace Tests\Feature;

use App\Models\BloodUnit;
use App\Models\DonationCard;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_donation_card_qr_verifies_live_record_without_private_contact_or_identity_data(): void
    {
        $this->seed(DemoDataSeeder::class);
        $card = DonationCard::with('donor')->where('card_number', 'CARD-DEMO-V2-0001')->firstOrFail();

        $response = $this->get(route('trace.card', $card->qr_token));

        $response->assertOk()
            ->assertSee($card->card_number)
            ->assertSee($card->donor->full_name)
            ->assertSee($card->donor->blood_group)
            ->assertSee(__('bloodcare.traceability.card.title'));

        foreach ([$card->donor->phone, $card->donor->identity_number, $card->donor->address] as $privateValue) {
            if ($privateValue) {
                $response->assertDontSee($privateValue);
            }
        }
    }

    public function test_blood_unit_qr_shows_hospital_lifecycle_without_donor_or_patient_identity(): void
    {
        $this->seed(DemoDataSeeder::class);
        $unit = BloodUnit::with(['donation.donor', 'allocations.request.hospital'])
            ->where('unit_number', 'BU-DEMO-V2-TX01')
            ->firstOrFail();
        $allocation = $unit->allocations->firstOrFail();

        $response = $this->get(route('trace.blood-unit', $unit->trace_token));

        $response->assertOk()
            ->assertSee($unit->unit_number)
            ->assertSee(__('bloodcare.traceability.unit.transfused_event'))
            ->assertSee($allocation->request->hospital->name)
            ->assertDontSee($unit->donation->donor->full_name)
            ->assertDontSee($allocation->request->patient_reference);
    }

    public function test_component_qr_trace_preserves_plasma_to_cryo_lineage(): void
    {
        $this->seed(DemoDataSeeder::class);
        $cryo = BloodUnit::where('unit_number', 'BU-DEMO-V2-CMP01-CRYO')->firstOrFail();

        $this->get(route('trace.blood-unit', $cryo->trace_token))
            ->assertOk()
            ->assertSee('BU-DEMO-V2-CMP01')
            ->assertSee('BU-DEMO-V2-CMP01-PLS')
            ->assertSee('BU-DEMO-V2-CMP01-CRYO')
            ->assertSee(__('bloodcare.national.components.types.cryoprecipitate'));
    }

    public function test_lost_card_reissue_rotates_qr_and_invalidates_the_old_trace_url(): void
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::where('email', 'demo.admin@bloodcare.test')->firstOrFail();
        $card = DonationCard::where('card_number', 'CARD-DEMO-V2-0001')->firstOrFail();
        $oldToken = $card->qr_token;
        backpack_auth()->login($admin);

        $this->putJson(route('bloodcare.admin.cards.update', $card->card_number), [
            'issueDate' => now()->toDateString(),
            'expiryDate' => now()->addYears(5)->toDateString(),
            'status' => 'Active',
            'replacementCount' => $card->replacement_count + 1,
            'printCount' => $card->print_count + 1,
            'notes' => 'Lost-card QR rotation regression test.',
            'action' => 'Lost card replaced',
        ])->assertOk();

        $newToken = $card->fresh()->qr_token;
        $this->assertNotSame($oldToken, $newToken);
        $this->get(route('trace.card', $oldToken))->assertNotFound();
        $this->get(route('trace.card', $newToken))->assertOk();
    }
}
