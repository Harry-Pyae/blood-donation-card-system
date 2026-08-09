<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCard;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Support\MyanmarNrc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        $centres = DonationCentre::query()
            ->where('is_active', true)
            ->orderBy('region')
            ->orderBy('township')
            ->orderBy('name')
            ->get();

        $availableInventory = BloodUnit::query()
            ->where('status', 'available')
            ->whereDate('expires_at', '>=', now()->toDateString());

        $inventoryByGroup = (clone $availableInventory)
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $priorityGroups = collect($bloodGroups)
            ->sortBy(fn (string $group): int => (int) ($inventoryByGroup[$group] ?? 0))
            ->take(4)
            ->values();

        return view('public.home', [
            'centres' => $centres,
            'priorityGroups' => $priorityGroups,
            'inventoryByGroup' => $inventoryByGroup,
            'homeStats' => [
                'donors' => Donor::query()->where('status', '!=', 'inactive')->count(),
                'donations' => Donation::query()->where('status', 'accepted')->count(),
                'inventory' => (clone $availableInventory)->count(),
                'centres' => $centres->count(),
            ],
        ]);
    }

    public function donorRegistration(): View
    {
        return view('public.donor-register', [
            ...$this->nrcReference(),
            'maxBirthDate' => now()->subYears(18)->toDateString(),
        ]);
    }

    public function storeDonorRegistration(Request $request): View
    {
        if ($request->filled('phone_local')) {
            $localPhone = preg_replace('/[^0-9]+/', '', strtr((string) $request->input('phone_local'), [
                '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
                '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
            ])) ?? '';
            $request->merge([
                'phone_local' => $localPhone,
                'phone' => '+95 '.$localPhone,
            ]);
        }

        $request->merge([
            'nrc_serial' => MyanmarNrc::normalizeSerial($request->input('nrc_serial')),
        ]);

        $nrcReference = $this->nrcReference();
        $selectedState = (string) $request->input('nrc_state', '');
        $stateTownships = $nrcReference['nrcTownships'][$selectedState] ?? [];
        $allowedTownships = array_column($stateTownships, 'value');

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'gender' => ['required', Rule::in(['female', 'male', 'other'])],
            'identity_document_type' => ['required', Rule::in(['nrc', 'passport'])],
            'nrc_state' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in(array_keys($nrcReference['nrcStates']))],
            'nrc_township' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in($allowedTownships)],
            'nrc_type' => ['exclude_unless:identity_document_type,nrc', 'required', Rule::in(array_keys($nrcReference['nrcTypes']))],
            'nrc_serial' => ['exclude_unless:identity_document_type,nrc', 'required', 'regex:/^[0-9]{6}$/'],
            'passport_number' => ['exclude_unless:identity_document_type,passport', 'required', 'string', 'min:5', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'phone_local' => ['nullable', 'regex:/^[1-9][0-9]{6,11}$/'],
            'phone' => ['required', 'string', 'regex:/^\+95\s?[1-9][0-9]{6,11}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'blood_group' => ['required', Rule::in(['unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'emergency_contact' => ['required', 'string', 'max:120'],
            'previous_donation' => ['required', Rule::in(['yes', 'no'])],
            'health_notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], [], [
            'date_of_birth' => __('bloodcare.public.registration.date_of_birth'),
            'phone_local' => __('bloodcare.public.registration.phone'),
            'phone' => __('bloodcare.public.registration.phone'),
            'identity_document_type' => __('bloodcare.public.registration.document_type'),
            'nrc_state' => __('bloodcare.public.registration.nrc_state'),
            'nrc_township' => __('bloodcare.public.registration.nrc_township'),
            'nrc_type' => __('bloodcare.public.registration.nrc_type'),
            'nrc_serial' => __('bloodcare.public.registration.nrc_serial'),
            'passport_number' => __('bloodcare.public.registration.passport_number'),
        ]);

        $data['phone'] = '+95 '.substr(Donor::normalizePhone($data['phone']), 2);

        if ($data['identity_document_type'] === 'nrc') {
            $selectedTownship = collect($stateTownships)->firstWhere('value', $data['nrc_township']);
            $data['identity_number'] = sprintf(
                '%s/%s(%s)%s',
                $data['nrc_state'],
                $selectedTownship['display'],
                $data['nrc_type'],
                $data['nrc_serial'],
            );
        } else {
            $data['identity_number'] = strtoupper($data['passport_number']);
        }

        if (Donor::where('identity_number', $data['identity_number'])->exists()) {
            throw ValidationException::withMessages([
                'identity_document_type' => __('bloodcare.public.registration.identity_exists'),
            ]);
        }

        $donor = DB::transaction(function () use ($data): Donor {
            $donor = Donor::create([
                ...$data,
                'reference' => Donor::generateReference(),
                'phone_normalized' => Donor::normalizePhone($data['phone']),
                'previous_donation' => $data['previous_donation'] === 'yes',
                'consent_at' => now(),
                'status' => 'pending',
                'eligibility_status' => 'review',
            ]);

            ActivityLog::record([
                'type' => 'Donor',
                'action' => 'Public donor registration submitted',
                'subject_type' => Donor::class,
                'subject_id' => $donor->id,
                'donor_id' => $donor->id,
                'result' => 'Pending',
                'details' => 'A new donor registration is awaiting staff review.',
                'source' => 'public-registration',
            ]);

            return $donor;
        });

        return view('public.result', [
            'eyebrow' => __('bloodcare.public.registration.result_eyebrow'),
            'title' => __('bloodcare.public.registration.result_title'),
            'message' => __('bloodcare.public.registration.result_message'),
            'reference' => $donor->reference,
            'details' => [
                __('bloodcare.public.registration.result_name') => $donor->full_name,
                __('bloodcare.public.registration.result_phone') => $donor->phone,
                __('bloodcare.public.registration.result_group') => $donor->blood_group === 'unknown'
                    ? __('bloodcare.public.registration.result_group_unknown')
                    : $donor->blood_group,
                __('bloodcare.public.registration.result_status') => __('bloodcare.public.registration.result_pending'),
            ],
            'notice' => __('bloodcare.public.registration.result_notice'),
            'primaryUrl' => route('appointments.book'),
            'primaryLabel' => __('bloodcare.public.registration.result_primary'),
            'secondaryUrl' => route('home'),
            'secondaryLabel' => __('bloodcare.public.registration.result_secondary'),
        ]);
    }

    public function appointmentBooking(): View
    {
        return view('public.appointment-book', [
            'centres' => DonationCentre::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeAppointment(Request $request): View
    {
        $activeCentreNames = DonationCentre::query()
            ->where('is_active', true)
            ->pluck('name')
            ->all();

        $data = $request->validate([
            'donor_reference' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
            'centre' => ['required', Rule::in([...$activeCentreNames, '__request__'])],
            'appointment_date' => ['nullable', 'required_unless:centre,__request__', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addMonths(3)->toDateString()],
            'appointment_time' => ['nullable', 'required_unless:centre,__request__', Rule::in(['09:00', '10:30', '13:00', '14:30'])],
            'requested_region' => ['nullable', 'required_if:centre,__request__', 'string', 'max:120'],
            'requested_township' => ['nullable', 'required_if:centre,__request__', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'booking_acknowledgement' => ['accepted'],
        ]);

        $donor = Donor::query()
            ->whereRaw('UPPER(reference) = ?', [strtoupper(trim($data['donor_reference']))])
            ->where('phone_normalized', Donor::normalizePhone($data['phone']))
            ->first();

        if (! $donor) {
            throw ValidationException::withMessages([
                'donor_reference' => __('bloodcare.public.booking.donor_not_found'),
            ]);
        }

        $centre = $data['centre'] === '__request__'
            ? null
            : DonationCentre::where('name', $data['centre'])->where('is_active', true)->first();

        $appointment = DB::transaction(function () use ($data, $donor, $centre): Appointment {
            $appointment = Appointment::create([
                'reference' => Appointment::generateReference(),
                'donor_id' => $donor->id,
                'donation_centre_id' => $centre?->id,
                'centre_name' => $centre?->name,
                'appointment_date' => $data['appointment_date'] ?? null,
                'appointment_time' => $data['appointment_time'] ?? null,
                'requested_region' => $data['requested_region'] ?? null,
                'requested_township' => $data['requested_township'] ?? null,
                'purpose' => $donor->eligibility_status === 'eligible' ? 'Donation' : 'Eligibility review',
                'source' => 'Public booking',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'acknowledged_at' => now(),
            ]);

            ActivityLog::record([
                'type' => 'Appointment',
                'action' => $centre ? 'Public appointment booked' : 'Donation location requested',
                'subject_type' => Appointment::class,
                'subject_id' => $appointment->id,
                'donor_id' => $donor->id,
                'result' => 'Pending',
                'details' => $centre
                    ? "Appointment requested at {$centre->name}."
                    : 'A donor requested a donation location in another area.',
                'source' => 'public-booking',
            ]);

            return $appointment;
        });

        if (! $centre) {
            return view('public.result', [
                'eyebrow' => __('bloodcare.public.booking.location_result_eyebrow'),
                'title' => __('bloodcare.public.booking.location_result_title'),
                'message' => __('bloodcare.public.booking.location_result_message'),
                'reference' => $appointment->reference,
                'details' => [
                    __('bloodcare.public.booking.result_donor') => $donor->reference,
                    __('bloodcare.public.booking.region') => $appointment->requested_region,
                    __('bloodcare.public.booking.township') => $appointment->requested_township,
                    __('bloodcare.public.booking.result_status') => __('bloodcare.public.booking.location_result_status'),
                ],
                'notice' => __('bloodcare.public.booking.location_result_notice'),
                'primaryUrl' => route('appointments.book'),
                'primaryLabel' => __('bloodcare.public.booking.location_result_primary'),
                'secondaryUrl' => route('home'),
                'secondaryLabel' => __('bloodcare.public.booking.result_secondary'),
            ]);
        }

        return view('public.result', [
            'eyebrow' => __('bloodcare.public.booking.result_eyebrow'),
            'title' => __('bloodcare.public.booking.result_title'),
            'message' => __('bloodcare.public.booking.result_message'),
            'reference' => $appointment->reference,
            'details' => [
                __('bloodcare.public.booking.result_donor') => $donor->reference,
                __('bloodcare.public.booking.summary_centre') => $appointment->centre_name,
                __('bloodcare.public.booking.summary_date') => $appointment->appointment_date?->toDateString(),
                __('bloodcare.public.booking.summary_time') => substr((string) $appointment->appointment_time, 0, 5),
                __('bloodcare.public.booking.result_status') => __('bloodcare.public.booking.result_pending'),
            ],
            'notice' => __('bloodcare.public.booking.result_notice'),
            'primaryUrl' => route('appointments.check'),
            'primaryLabel' => __('bloodcare.public.booking.result_primary'),
            'secondaryUrl' => route('home'),
            'secondaryLabel' => __('bloodcare.public.booking.result_secondary'),
        ]);
    }

    public function appointmentLookup(): View
    {
        return view('public.lookup', [
            'type' => 'appointment',
            'eyebrow' => __('bloodcare.public.lookup.appointment_eyebrow'),
            'title' => __('bloodcare.public.lookup.appointment_heading'),
            'description' => __('bloodcare.public.lookup.appointment_description'),
            'action' => route('appointments.check.show'),
            'referenceLabel' => __('bloodcare.public.lookup.appointment_reference'),
            'referencePlaceholder' => __('bloodcare.public.lookup.appointment_placeholder'),
        ]);
    }

    public function showAppointment(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $appointment = Appointment::query()
            ->with(['donor', 'centre'])
            ->whereRaw('UPPER(reference) = ?', [strtoupper(trim($data['reference']))])
            ->whereHas('donor', fn ($query) => $query->where('phone_normalized', Donor::normalizePhone($data['phone'])))
            ->first();

        if (! $appointment) {
            throw ValidationException::withMessages([
                'reference' => __('bloodcare.public.lookup.record_not_found'),
            ]);
        }

        $dateTime = $appointment->appointment_date
            ? $appointment->appointment_date->format('d M Y').($appointment->appointment_time ? ' · '.substr((string) $appointment->appointment_time, 0, 5) : '')
            : __('bloodcare.public.lookup.location_request_pending');

        return view('public.result', [
            'lookupType' => 'appointment',
            'eyebrow' => __('bloodcare.public.lookup.appointment_result_eyebrow'),
            'title' => __('bloodcare.public.lookup.appointment_result_title'),
            'message' => __('bloodcare.public.lookup.appointment_result_message'),
            'reference' => $appointment->reference,
            'details' => [
                __('bloodcare.public.lookup.result_centre') => $appointment->centre_name
                    ?? trim(($appointment->requested_region ?? '').' / '.($appointment->requested_township ?? ''), ' /'),
                __('bloodcare.public.lookup.result_date_time') => $dateTime,
                __('bloodcare.public.lookup.result_purpose') => $appointment->purpose,
                __('bloodcare.public.lookup.result_status') => ucfirst(str_replace('_', ' ', $appointment->status)),
            ],
            'notice' => __('bloodcare.public.lookup.live_notice'),
            'primaryUrl' => route('appointments.book'),
            'primaryLabel' => __('bloodcare.public.lookup.book_another'),
            'secondaryUrl' => route('home'),
            'secondaryLabel' => __('bloodcare.public.lookup.return_home'),
        ]);
    }

    public function cardLookup(): View
    {
        return view('public.lookup', [
            'type' => 'card',
            'eyebrow' => __('bloodcare.public.lookup.card_eyebrow'),
            'title' => __('bloodcare.public.lookup.card_heading'),
            'description' => __('bloodcare.public.lookup.card_description'),
            'action' => route('card.check.show'),
            'referenceLabel' => __('bloodcare.public.lookup.card_reference'),
            'referencePlaceholder' => __('bloodcare.public.lookup.card_placeholder'),
        ]);
    }

    public function showCard(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $card = DonationCard::query()
            ->with(['donor.donations'])
            ->whereRaw('UPPER(card_number) = ?', [strtoupper(trim($data['reference']))])
            ->whereHas('donor', fn ($query) => $query->where('phone_normalized', Donor::normalizePhone($data['phone'])))
            ->first();

        if (! $card) {
            throw ValidationException::withMessages([
                'reference' => __('bloodcare.public.lookup.record_not_found'),
            ]);
        }

        return view('public.result', [
            'lookupType' => 'card',
            'eyebrow' => __('bloodcare.public.lookup.card_result_eyebrow'),
            'title' => __('bloodcare.public.lookup.card_result_title'),
            'message' => __('bloodcare.public.lookup.card_result_message'),
            'reference' => $card->card_number,
            'card' => [
                'name' => $card->donor->full_name,
                'blood_group' => $card->donor->blood_group,
                'last_donation' => $card->donor->last_donation_date?->format('d M Y') ?? '—',
                'next_eligible' => $card->donor->next_eligible_date?->format('d M Y') ?? __('bloodcare.public.lookup.eligible_now'),
                'total_donations' => $card->donor->donations->count(),
                'status' => ucfirst($card->status),
            ],
            'details' => [],
            'notice' => __('bloodcare.public.lookup.live_notice'),
            'primaryUrl' => route('appointments.book'),
            'primaryLabel' => __('bloodcare.public.lookup.book_donation'),
            'secondaryUrl' => route('home'),
            'secondaryLabel' => __('bloodcare.public.lookup.return_home'),
        ]);
    }

    public function eligibility(): View
    {
        return view('public.eligibility');
    }

    public function about(): View
    {
        return view('public.about');
    }

    private function nrcReference(): array
    {
        return MyanmarNrc::reference();
    }

}
