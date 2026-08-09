<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\User;
use App\Services\DonorScreeningService;
use App\Support\MyanmarNrc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DonorManagementController extends Controller
{
    public function create(Request $request): View
    {
        $registrationMode = in_array($request->query('mode'), ['linked', 'new'], true)
            ? (string) $request->query('mode')
            : 'new';

        return view('admin.donors.form', $this->formData(null, $registrationMode));
    }

    public function edit(Donor $donor): View
    {
        return view('admin.donors.form', $this->formData($donor));
    }

    public function show(Request $request, Donor $donor): View
    {
        $donor->load([
            'user',
            'card',
            'latestScreening.appointment',
            'latestScreening.centre',
            'latestScreening.verifiedBy',
        ]);

        $donations = $donor->donations()
            ->with(['appointment', 'centre', 'recordedBy', 'bloodUnit', 'screening'])
            ->when($request->filled('donation_search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->input('donation_search')).'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('reference', 'like', $search)
                        ->orWhere('bag_unit_number', 'like', $search)
                        ->orWhere('blood_group', 'like', $search)
                        ->orWhere('storage_location', 'like', $search)
                        ->orWhere('screening_notes', 'like', $search)
                        ->orWhereHas('appointment', fn ($appointment) => $appointment->where('reference', 'like', $search))
                        ->orWhereHas('appointment', fn ($appointment) => $appointment->where('centre_name', 'like', $search))
                        ->orWhereHas('centre', fn ($centre) => $centre->where('name', 'like', $search))
                        ->orWhereHas('recordedBy', fn ($staff) => $staff->where('name', 'like', $search));
                });
            })
            ->when($request->filled('donation_type'), fn ($query) => $query->where('donation_type', $request->input('donation_type')))
            ->when($request->filled('donation_status'), fn ($query) => $query->where('status', $request->input('donation_status')))
            ->when($request->filled('donation_from'), fn ($query) => $query->whereDate('donation_date', '>=', $request->input('donation_from')))
            ->when($request->filled('donation_to'), fn ($query) => $query->whereDate('donation_date', '<=', $request->input('donation_to')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'donations_page')
            ->withQueryString()
            ->fragment('donation-history');

        $screenings = $donor->screenings()
            ->with(['appointment', 'donation', 'centre', 'verifiedBy'])
            ->when($request->filled('screening_search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->input('screening_search')).'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('reference', 'like', $search)
                        ->orWhere('deferral_reason', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhere('current_medications', 'like', $search)
                        ->orWhere('recent_travel_details', 'like', $search)
                        ->orWhere('high_risk_activity_details', 'like', $search)
                        ->orWhereHas('centre', fn ($centre) => $centre->where('name', 'like', $search))
                        ->orWhereHas('verifiedBy', fn ($staff) => $staff->where('name', 'like', $search));
                });
            })
            ->when($request->filled('screening_outcome'), fn ($query) => $query->where('outcome', $request->input('screening_outcome')))
            ->when($request->filled('screening_from'), fn ($query) => $query->whereDate('screened_at', '>=', $request->input('screening_from')))
            ->when($request->filled('screening_to'), fn ($query) => $query->whereDate('screened_at', '<=', $request->input('screening_to')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'screenings_page')
            ->withQueryString()
            ->fragment('screening-history');

        return view('admin.donors.show', [
            'donor' => $donor,
            'donations' => $donations,
            'screenings' => $screenings,
            'appointments' => $donor->appointments()
                ->with('centre')
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get(),
            'centres' => DonationCentre::query()->where('is_active', true)->orderBy('name')->get(),
            'donationCount' => $donor->donations()->count(),
            'acceptedDonationCount' => $donor->donations()->where('status', 'accepted')->count(),
            'screeningCount' => $donor->screenings()->count(),
            'today' => now()->toDateString(),
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(
        Request $request,
        DonorScreeningService $screeningService,
    ): JsonResponse|RedirectResponse {
        $data = $this->validated($request);

        $donor = DB::transaction(function () use ($data, $screeningService): Donor {
            $donor = Donor::create($this->attributes($data));

            if ($data['recordInitialScreening'] ?? false) {
                $screeningService->record($donor, $data, backpack_user());
            }

            $this->log($donor, 'Donor registered by staff');

            return $donor;
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Donor saved.', 'donor' => $donor], 201);
        }

        return redirect()
            ->route('bloodcare.admin.donors.show', $donor)
            ->with('status', __('bloodcare.donor_details.donor_registered'));
    }

    public function update(Request $request, Donor $donor): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request, $donor);

        DB::transaction(function () use ($donor, $data): void {
            $donor->update($this->attributes($data, $donor));
            $this->log($donor, 'Donor updated by staff');
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Donor updated.', 'donor' => $donor->fresh()]);
        }

        return redirect()
            ->route('bloodcare.admin.donors.show', $donor)
            ->with('status', __('bloodcare.donor_details.donor_updated'));
    }

    private function validated(Request $request, ?Donor $donor = null): array
    {
        if ($request->filled('phoneLocal')) {
            $localPhone = preg_replace('/[^0-9]+/', '', strtr((string) $request->input('phoneLocal'), [
                '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
                '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
            ])) ?? '';
            $request->merge([
                'phoneLocal' => $localPhone,
                'phone' => '+95 '.$localPhone,
            ]);
        }

        $request->merge([
            'registrationMode' => $request->input(
                'registrationMode',
                ($request->filled('userId') || $donor?->user_id) ? 'linked' : 'new'
            ),
            'nrcSerial' => MyanmarNrc::normalizeSerial($request->input('nrcSerial')),
            'passportNumber' => strtoupper(trim((string) $request->input('passportNumber', ''))),
            'recordInitialScreening' => $request->boolean('recordInitialScreening'),
            'donationTypePreference' => $request->input('donationTypePreference', $donor?->donation_type_preference ?? 'whole_blood'),
            'deferralType' => $request->input('deferralType', $donor?->deferral_type ?? 'none'),
            'medicationFlag' => $request->boolean('medicationFlag'),
            'recentTravelFlag' => $request->boolean('recentTravelFlag'),
            'highRiskActivityFlag' => $request->boolean('highRiskActivityFlag'),
        ]);

        $nrcReference = MyanmarNrc::reference();
        $selectedState = (string) $request->input('nrcState', '');
        $stateTownships = $nrcReference['nrcTownships'][$selectedState] ?? [];
        $allowedTownships = array_column($stateTownships, 'value');

        $data = $request->validate([
            'registrationMode' => ['nullable', Rule::in(['linked', 'new'])],
            'userId' => [
                Rule::requiredIf(! $donor && $request->input('registrationMode') === 'linked'),
                Rule::prohibitedIf(! $donor && $request->input('registrationMode') === 'new'),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($donor): void {
                    $query->where(function ($eligible): void {
                        $eligible->where('role', User::ROLE_USER)
                            ->where('approval_status', User::APPROVAL_APPROVED)
                            ->where('is_banned', false);
                    });

                    if ($donor?->user_id) {
                        $query->orWhere('id', $donor->user_id);
                    }
                }),
                Rule::unique('donors', 'user_id')->ignore($donor?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'group' => ['required', Rule::in(['unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'phoneLocal' => ['nullable', 'regex:/^[1-9][0-9]{6,11}$/'],
            'phone' => ['required', 'string', 'regex:/^\+95\s?[1-9][0-9]{6,11}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'emergencyContact' => ['nullable', 'string', 'max:120'],
            'dateOfBirth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other', 'male', 'female', 'other'])],
            'identityDocumentType' => ['required', Rule::in(['nrc', 'passport'])],
            'nrcState' => ['exclude_unless:identityDocumentType,nrc', 'required', Rule::in(array_keys($nrcReference['nrcStates']))],
            'nrcTownship' => ['exclude_unless:identityDocumentType,nrc', 'required', Rule::in($allowedTownships)],
            'nrcType' => ['exclude_unless:identityDocumentType,nrc', 'required', Rule::in(array_keys($nrcReference['nrcTypes']))],
            'nrcSerial' => ['exclude_unless:identityDocumentType,nrc', 'required', 'regex:/^[0-9]{6}$/'],
            'passportNumber' => [
                'exclude_unless:identityDocumentType,passport', 'required', 'string',
                'min:5', 'max:20', 'regex:/^[A-Za-z0-9-]+$/',
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'donationTypePreference' => ['required', Rule::in(['whole_blood', 'platelets', 'plasma'])],
            'lastDonation' => ['nullable', 'date', 'before_or_equal:today'],
            'nextEligible' => ['nullable'],
            'eligibility' => ['required', Rule::in(['Eligible', 'Review', 'Deferred'])],
            'status' => ['required', Rule::in(['Active', 'Pending', 'Inactive'])],
            'deferralType' => ['required', Rule::in(['none', 'temporary', 'permanent'])],
            'deferralReason' => ['nullable', 'required_unless:deferralType,none', 'string', 'max:255'],
            'deferralEndDate' => ['nullable', 'required_if:deferralType,temporary', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recordInitialScreening' => ['required', 'boolean'],
        ], [], [
            'phoneLocal' => __('bloodcare.donors.phone'),
            'phone' => __('bloodcare.donors.phone'),
            'dateOfBirth' => __('bloodcare.donors.date_of_birth'),
            'identityDocumentType' => __('bloodcare.public.registration.document_type'),
            'nrcState' => __('bloodcare.public.registration.nrc_state'),
            'nrcTownship' => __('bloodcare.public.registration.nrc_township'),
            'nrcType' => __('bloodcare.public.registration.nrc_type'),
            'nrcSerial' => __('bloodcare.public.registration.nrc_serial'),
            'passportNumber' => __('bloodcare.public.registration.passport_number'),
        ]);

        if ($request->boolean('recordInitialScreening')) {
            $data = array_merge($data, $request->validate(DonorScreeningController::rules()));
            $requiresDeferral = in_array($data['screeningOutcome'], ['Deferred', 'Failed'], true);

            if ($requiresDeferral && $data['screeningDeferralType'] === 'none') {
                throw ValidationException::withMessages([
                    'screeningDeferralType' => __('bloodcare.donor_details.screening_deferral_required'),
                ]);
            }
            if (! $requiresDeferral && $data['screeningDeferralType'] !== 'none') {
                throw ValidationException::withMessages([
                    'screeningDeferralType' => __('bloodcare.donor_details.screening_deferral_not_allowed'),
                ]);
            }
        }

        if ($donor && $data['group'] !== $donor->blood_group) {
            throw ValidationException::withMessages([
                'group' => 'A donor\'s blood group cannot be changed after registration.',
            ]);
        }

        $identity = $data['identityDocumentType'] === 'nrc'
            ? MyanmarNrc::buildIdentity(
                $data['nrcState'],
                $data['nrcTownship'],
                $data['nrcType'],
                $data['nrcSerial'],
                $stateTownships,
            )
            : strtoupper($data['passportNumber']);

        if ($identity === '') {
            throw ValidationException::withMessages([
                'nrcTownship' => __('bloodcare.public.registration.select_township'),
            ]);
        }

        $identityExists = Donor::query()
            ->where('identity_number', $identity)
            ->when($donor, fn ($query) => $query->where('id', '!=', $donor->id))
            ->exists();

        if ($identityExists) {
            throw ValidationException::withMessages([
                'identityDocumentType' => __('bloodcare.public.registration.identity_exists'),
            ]);
        }

        $data['phone'] = '+95 '.substr(Donor::normalizePhone($data['phone']), 2);
        $data['identity'] = $identity;

        return $data;
    }

    private function attributes(array $data, ?Donor $donor = null): array
    {
        $eligibility = strtolower($data['eligibility']);
        $nextEligible = in_array($data['nextEligible'] ?? null, [null, '', 'now', 'review'], true)
            ? null
            : $data['nextEligible'];
        $documentType = $data['identityDocumentType'];
        $deferralType = strtolower($data['deferralType']);

        if ($deferralType !== 'none') {
            $eligibility = 'deferred';
            $nextEligible = $deferralType === 'temporary'
                ? ($data['deferralEndDate'] ?? $nextEligible)
                : null;
        }

        return [
            'reference' => $donor?->reference ?? Donor::generateReference(),
            'user_id' => $data['userId'] ?? null,
            'full_name' => trim($data['name']),
            'date_of_birth' => $data['dateOfBirth'],
            'gender' => strtolower($data['gender']),
            'identity_document_type' => $documentType,
            'identity_number' => $data['identity'],
            'nrc_state' => $documentType === 'nrc' ? $data['nrcState'] : null,
            'nrc_township' => $documentType === 'nrc' ? $data['nrcTownship'] : null,
            'nrc_type' => $documentType === 'nrc' ? $data['nrcType'] : null,
            'nrc_serial' => $documentType === 'nrc' ? $data['nrcSerial'] : null,
            'passport_number' => $documentType === 'passport' ? strtoupper($data['passportNumber']) : null,
            'phone' => trim($data['phone']),
            'phone_normalized' => Donor::normalizePhone($data['phone']),
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: 'Not provided',
            'blood_group' => $donor?->blood_group ?? $data['group'],
            'donation_type_preference' => $data['donationTypePreference'],
            'emergency_contact' => trim($data['emergencyContact'] ?? '') ?: trim($data['phone']),
            'previous_donation' => ! empty($data['lastDonation']),
            'consent_at' => $donor?->consent_at ?? now(),
            'status' => strtolower($data['status']),
            'eligibility_status' => $eligibility,
            'deferral_type' => $deferralType,
            'deferral_reason' => $deferralType === 'none' ? null : ($data['deferralReason'] ?? null),
            'deferral_end_date' => $deferralType === 'temporary' ? ($data['deferralEndDate'] ?? null) : null,
            'last_donation_date' => $data['lastDonation'] ?: null,
            'next_eligible_date' => $nextEligible,
            'staff_notes' => $data['notes'] ?: null,
        ];
    }

    /** @return array<string, mixed> */
    private function formData(?Donor $donor = null, string $registrationMode = 'new'): array
    {
        $users = User::query()
            ->where(function ($query) use ($donor): void {
                $query->where(function ($eligible): void {
                    $eligible->where('role', User::ROLE_USER)
                        ->where('approval_status', User::APPROVAL_APPROVED)
                        ->where('is_banned', false)
                        ->whereDoesntHave('donor');
                });

                if ($donor?->user_id) {
                    $query->orWhereKey($donor->user_id);
                }
            })
            ->orderBy('name')
            ->limit(1000)
            ->get();

        return [
            'donor' => $donor,
            'registrationMode' => $registrationMode,
            'publicUsers' => $users,
            'centres' => DonationCentre::query()->where('is_active', true)->orderBy('name')->get(),
            'appointments' => $donor
                ? $donor->appointments()->with('centre')->orderByDesc('appointment_date')->get()
                : collect(),
            'nrcReference' => MyanmarNrc::reference(),
            'today' => now()->toDateString(),
            'maxBirthDate' => now()->subYears(18)->toDateString(),
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ];
    }

    private function log(Donor $donor, string $action): void
    {
        ActivityLog::record([
            'type' => 'Donor',
            'action' => $action,
            'subject_type' => Donor::class,
            'subject_id' => $donor->id,
            'donor_id' => $donor->id,
            'user_id' => backpack_user()?->id,
            'result' => ucfirst($donor->eligibility_status),
            'details' => "{$donor->reference}; {$donor->full_name}; {$donor->status}",
            'source' => 'admin-donors',
        ]);
    }
}
