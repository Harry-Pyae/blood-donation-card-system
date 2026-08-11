<?php

namespace App\Http\Controllers;

use App\Actions\Public\BookAppointmentAction;
use App\Actions\Public\LookupPublicRecordAction;
use App\Actions\Public\RegisterDonorAction;
use App\Http\Requests\Public\PublicLookupRequest;
use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Http\Requests\Public\StoreDonorRegistrationRequest;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Support\MyanmarNrc;
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

    public function storeDonorRegistration(
        StoreDonorRegistrationRequest $request,
        RegisterDonorAction $registerDonor,
    ): View {
        $donor = $registerDonor->handle($request->validated(), $request->stateTownships());

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

    public function storeAppointment(
        StoreAppointmentRequest $request,
        BookAppointmentAction $bookAppointment,
    ): View {
        ['donor' => $donor, 'appointment' => $appointment, 'centre' => $centre] =
            $bookAppointment->handle($request->validated());

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

    public function showAppointment(
        PublicLookupRequest $request,
        LookupPublicRecordAction $lookup,
    ): View {
        $data = $request->validated();
        $appointment = $lookup->appointment($data['reference'], $data['phone']);

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

    public function showCard(
        PublicLookupRequest $request,
        LookupPublicRecordAction $lookup,
    ): View {
        $data = $request->validated();
        $card = $lookup->card($data['reference'], $data['phone']);

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
