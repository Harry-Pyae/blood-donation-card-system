<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AdverseReaction;
use App\Models\Appointment;
use App\Models\BloodAllocation;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\DonationCard;
use App\Models\DonationCentre;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\User;
use App\Notifications\BloodCareWorkflowNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public const DEMO_PASSWORD = 'BloodCare!Demo2026';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->error('Demo data is allowed only in the local or testing environment.');

            return;
        }

        DB::transaction(function (): void {
            $hospitals = $this->seedHospitals();
            $users = $this->seedUsers($hospitals);
            $centres = $this->seedCentres();
            $donors = $this->seedDonors($users);
            $appointments = $this->seedAppointments($donors, $centres, $users['staff']);
            $this->seedCards($donors, $users['admin']);
            $donations = $this->seedDonations($donors, $centres, $appointments, $users['staff']);
            $screenings = $this->seedScreenings($donors, $centres, $appointments, $donations, $users['staff']);
            $units = $this->seedBloodUnits($donations);
            $labTests = $this->seedLabTests($donations, $units, $users['lab_admin']);
            $components = $this->seedComponents($units, $users['lab_admin']);
            $requests = $this->seedHospitalRequests($hospitals, $users, $units, $components);
            $this->seedActivityLogs($users, $donors, $appointments, $donations, $screenings, $labTests, $requests);
            $this->seedNotifications($users);
            $this->ensureBloodUnitTraceTokens();
        });

        $this->command?->newLine();
        $this->command?->info('BloodCare current demo dataset is ready. See DEMO_DATA.md for scenario references and logins.');
    }

    private function ensureBloodUnitTraceTokens(): void
    {
        DB::table('blood_units')
            ->whereNull('trace_token')
            ->where('unit_number', 'like', 'BU-DEMO-V2-%')
            ->orderBy('id')
            ->get(['id', 'unit_number'])
            ->each(function ($unit): void {
                DB::table('blood_units')->where('id', $unit->id)->update([
                    'trace_token' => 'demo-v2-'.substr(hash('sha256', $unit->unit_number), 0, 48),
                ]);
            });
    }

    /** @return array<string, Hospital> */
    private function seedHospitals(): array
    {
        $definitions = [
            'yangon' => [
                'code' => 'HSP-DEMO-YGN-01',
                'name' => 'Yangon General Hospital (Demo)',
                'region' => 'Yangon Region',
                'address' => 'Bogyoke Aung San Road, Yangon',
                'phone' => '01 700 2001',
                'is_active' => true,
            ],
            'mandalay' => [
                'code' => 'HSP-DEMO-MDY-01',
                'name' => 'Mandalay General Hospital (Demo)',
                'region' => 'Mandalay Region',
                'address' => '30th Street, Chanayethazan, Mandalay',
                'phone' => '02 700 2002',
                'is_active' => true,
            ],
            'inactive' => [
                'code' => 'HSP-DEMO-BGO-01',
                'name' => 'Bago Community Hospital (Inactive Demo)',
                'region' => 'Bago Region',
                'address' => 'Bago, Myanmar',
                'phone' => '052 700 2003',
                'is_active' => false,
            ],
        ];

        $hospitals = [];
        foreach ($definitions as $key => $attributes) {
            $hospitals[$key] = Hospital::updateOrCreate(['code' => $attributes['code']], $attributes);
        }

        return $hospitals;
    }

    /** @param array<string, Hospital> $hospitals
     *  @return array<string, User>
     */
    private function seedUsers(array $hospitals): array
    {
        $definitions = [
            'admin' => ['demo.admin@bloodcare.test', [
                'name' => 'Demo System Administrator', 'phone' => '09 710 000 001',
                'job_title' => 'System Administrator', 'workplace' => 'BloodCare Headquarters',
                'role' => User::ROLE_ADMIN, 'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo System Administrator.', 'is_banned' => false,
            ]],
            'staff' => ['demo.staff@bloodcare.test', [
                'name' => 'Aye Thandar', 'phone' => '09 710 000 002',
                'job_title' => 'Blood Bank Officer', 'workplace' => 'Yangon Central BloodCare Centre',
                'role' => User::ROLE_STAFF, 'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo System Staff account.', 'is_banned' => false,
            ]],
            'lab_admin' => ['demo.lab.admin@bloodcare.test', [
                'name' => 'Dr. Hnin Ei', 'phone' => '09 710 000 003',
                'job_title' => 'Laboratory Doctor', 'workplace' => 'BloodCare Reference Laboratory',
                'role' => User::ROLE_LAB_ADMIN, 'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo Laboratory Administrator.', 'is_banned' => false,
            ]],
            'lab_staff' => ['demo.lab.staff@bloodcare.test', [
                'name' => 'Myo Min Tun', 'phone' => '09 710 000 004',
                'job_title' => 'Medical Laboratory Technician', 'workplace' => 'BloodCare Reference Laboratory',
                'role' => User::ROLE_LAB_STAFF, 'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo Laboratory Staff.', 'is_banned' => false,
            ]],
            'pending_staff' => ['pending.staff@bloodcare.test', [
                'name' => 'Ko Min Htet', 'phone' => '09 710 000 005',
                'job_title' => 'Nurse', 'workplace' => 'North Okkala BloodCare Centre',
                'role' => User::ROLE_STAFF, 'approval_status' => User::APPROVAL_PENDING,
                'registration_note' => 'Pending demo registration for approval testing.', 'is_banned' => false,
            ]],
            'rejected_staff' => ['rejected.staff@bloodcare.test', [
                'name' => 'Mya Mya Win', 'phone' => '09 710 000 006',
                'job_title' => 'Volunteer', 'workplace' => 'External Community Group',
                'role' => User::ROLE_STAFF, 'approval_status' => User::APPROVAL_REJECTED,
                'registration_note' => 'Rejected demo registration.', 'is_banned' => false,
            ]],
            'banned_staff' => ['banned.staff@bloodcare.test', [
                'name' => 'Banned Demo Staff', 'phone' => '09 710 000 007',
                'job_title' => 'Former Clerk', 'workplace' => 'BloodCare Headquarters',
                'role' => User::ROLE_STAFF, 'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Banned demo account.', 'is_banned' => true,
            ]],
            'public_user' => ['demo.donor@bloodcare.test', [
                'name' => 'Thiri Aung', 'phone' => '09 710 100 001',
                'job_title' => null, 'workplace' => null, 'role' => User::ROLE_USER,
                'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Public demo identity linked to BC-DEMO-V2-0001.', 'is_banned' => false,
            ]],
            'hospital_yangon' => ['demo.hospital.yangon@bloodcare.test', [
                'name' => 'Yangon Hospital Blood Desk', 'phone' => '09 710 200 001',
                'job_title' => 'Hospital Blood Bank Officer', 'workplace' => $hospitals['yangon']->name,
                'hospital_id' => $hospitals['yangon']->id, 'role' => User::ROLE_HOSPITAL,
                'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo hospital portal account.', 'is_banned' => false,
            ]],
            'hospital_mandalay' => ['demo.hospital.mandalay@bloodcare.test', [
                'name' => 'Mandalay Hospital Blood Desk', 'phone' => '09 710 200 002',
                'job_title' => 'Hospital Blood Bank Officer', 'workplace' => $hospitals['mandalay']->name,
                'hospital_id' => $hospitals['mandalay']->id, 'role' => User::ROLE_HOSPITAL,
                'approval_status' => User::APPROVAL_APPROVED,
                'registration_note' => 'Current demo hospital portal account.', 'is_banned' => false,
            ]],
        ];

        $users = [];
        foreach ($definitions as $key => [$email, $attributes]) {
            $users[$key] = User::updateOrCreate(['email' => $email], [
                ...$attributes,
                'password' => self::DEMO_PASSWORD,
            ]);
            $users[$key]->forceFill(['email_verified_at' => now()])->save();
        }

        $admin = $users['admin'];
        $admin->update(['approved_by' => $admin->id, 'approved_at' => now()->subDays(120)]);
        foreach (['staff', 'lab_admin', 'lab_staff', 'banned_staff', 'public_user', 'hospital_yangon', 'hospital_mandalay'] as $key) {
            $users[$key]->update(['approved_by' => $admin->id, 'approved_at' => now()->subDays(90)]);
        }
        $users['pending_staff']->update(['approved_by' => null, 'approved_at' => null]);
        $users['rejected_staff']->update(['approved_by' => $admin->id, 'approved_at' => null]);

        return $users;
    }

    /** @return array<string, DonationCentre> */
    private function seedCentres(): array
    {
        $base = [
            'yangon' => ['code' => 'CTR-0001', 'name' => 'Yangon Central', 'region' => 'Yangon Region', 'township' => 'Central Yangon', 'address' => 'No. 97, Shwedagon Pagoda Road, Yangon', 'phone' => '01 555 0123', 'opening_hours' => 'Mon-Sat, 08:30-16:30', 'is_active' => true],
            'north_okka' => ['code' => 'CTR-0002', 'name' => 'North Okkala', 'region' => 'Yangon Region', 'township' => 'North Okkalapa', 'address' => 'Thudhamma Road, North Okkalapa, Yangon', 'phone' => '01 555 0124', 'opening_hours' => 'Mon-Sat, 08:30-16:30', 'is_active' => true],
            'thingangyun' => ['code' => 'CTR-0003', 'name' => 'Thingangyun', 'region' => 'Yangon Region', 'township' => 'Thingangyun', 'address' => 'Lay Daungkan Road, Thingangyun, Yangon', 'phone' => '01 555 0125', 'opening_hours' => 'Mon-Sat, 08:30-16:30', 'is_active' => true],
        ];
        $demo = [
            'mandalay' => ['code' => 'CTR-DEMO-V2-0004', 'name' => 'Mandalay Central Demo', 'region' => 'Mandalay Region', 'township' => 'Chanayethazan', 'address' => '30th Street, Mandalay', 'phone' => '02 710 1001', 'opening_hours' => 'Mon-Sun, 09:00-17:00', 'is_active' => true],
            'inactive' => ['code' => 'CTR-DEMO-V2-0005', 'name' => 'Bago Mobile Unit Demo', 'region' => 'Bago Region', 'township' => 'Bago', 'address' => 'Temporary mobile collection unit', 'phone' => '052 710 1002', 'opening_hours' => 'Temporarily closed', 'is_active' => false],
        ];

        $centres = [];
        foreach ($base as $key => $attributes) {
            $centres[$key] = DonationCentre::firstOrCreate(['code' => $attributes['code']], $attributes);
        }
        foreach ($demo as $key => $attributes) {
            $centres[$key] = DonationCentre::updateOrCreate(['code' => $attributes['code']], $attributes);
        }

        return $centres;
    }

    /** @param array<string, User> $users
     *  @return array<string, Donor>
     */
    private function seedDonors(array $users): array
    {
        $definitions = [
            'd01' => ['Thiri Aung', '1997-04-12', 'female', 'A+', '09710100001', 210001, ['user_id' => $users['public_user']->id, 'previous_donation' => true, 'last_donation_date' => $this->date(0), 'next_eligible_date' => $this->date(90), 'eligibility_status' => 'deferred']],
            'd02' => ['Min Khant', '1994-11-03', 'male', 'A-', '09710100002', 210002, ['previous_donation' => true, 'last_donation_date' => $this->date(-3), 'next_eligible_date' => $this->date(87), 'eligibility_status' => 'deferred']],
            'd03' => ['Su Myat Noe', '2000-02-18', 'female', 'B+', '09710100003', 210003, ['status' => 'inactive', 'eligibility_status' => 'deferred', 'deferral_type' => 'temporary', 'deferral_reason' => 'Post-donation laboratory follow-up required.', 'deferral_end_date' => $this->date(60)]],
            'd04' => ['Ko Zaw Min', '1989-08-25', 'male', 'B-', '09710100004', 210004, ['previous_donation' => true, 'last_donation_date' => $this->date(-2), 'next_eligible_date' => $this->date(88), 'eligibility_status' => 'deferred']],
            'd05' => ['Ei Ei Mon', '1992-06-09', 'female', 'AB+', '09710100005', 210005, ['eligibility_status' => 'deferred', 'deferral_type' => 'temporary', 'deferral_reason' => 'Low haemoglobin at screening.', 'deferral_end_date' => $this->date(30), 'next_eligible_date' => $this->date(30)]],
            'd06' => ['Hnin Pwint', '1998-12-14', 'female', 'AB-', '09710100006', 210006, ['previous_donation' => true, 'last_donation_date' => $this->date(-1), 'next_eligible_date' => $this->date(89), 'eligibility_status' => 'deferred']],
            'd07' => ['Kyaw Zin Htet', '1995-03-30', 'male', 'O+', '09710100007', 210007, ['previous_donation' => true, 'last_donation_date' => $this->date(-2), 'next_eligible_date' => $this->date(88), 'eligibility_status' => 'deferred', 'staff_notes' => 'Full component-processing demo donor.']],
            'd08' => ['May Sandi', '1991-09-17', 'female', 'O-', '09710100008', 210008, ['previous_donation' => true, 'last_donation_date' => $this->date(-1), 'next_eligible_date' => $this->date(89), 'eligibility_status' => 'deferred']],
            'd09' => ['Nyein Chan', '1999-01-11', 'male', 'A+', '09710100009', 210009, ['status' => 'pending', 'eligibility_status' => 'review', 'health_notes' => 'Initial medical review is pending.']],
            'd10' => ['Moe Thuzar', '1987-07-07', 'female', 'B+', '09710100010', 210010, ['eligibility_status' => 'deferred', 'deferral_type' => 'permanent', 'deferral_reason' => 'Permanent clinical deferral (demo scenario).']],
            'd11' => ['History Demo Donor', '1990-10-10', 'male', 'A+', '09710100011', 210011, ['previous_donation' => true, 'last_donation_date' => $this->date(-120), 'staff_notes' => 'Multiple completed historical visits for history/search testing.']],
            'd12' => ['Ready Workflow Donor', '1996-05-21', 'female', 'B+', '09710100012', 210012, ['donation_type_preference' => 'platelets', 'staff_notes' => 'Checked-in appointment and passed screening; ready for staff to record a donation.']],
            'd13' => ['FEFO Component Donor', '1993-08-08', 'male', 'O+', '09710100013', 210013, ['previous_donation' => true, 'last_donation_date' => $this->date(0), 'next_eligible_date' => $this->date(90), 'eligibility_status' => 'deferred', 'staff_notes' => 'Partially processed O+ donation for staged processing and FEFO comparison.']],
            'd14' => ['Appointment Demo Donor', '1998-09-09', 'female', 'AB+', '09710100014', 210014, ['staff_notes' => 'Eligible donor reserved for clean future appointment scenarios.']],
        ];

        $donors = [];
        foreach ($definitions as $index => $definition) {
            [$name, $dob, $gender, $group, $phone, $serial, $overrides] = $definition;
            $reference = 'BC-DEMO-V2-'.str_pad((string) ((int) substr($index, 1)), 4, '0', STR_PAD_LEFT);
            $attributes = $this->donorAttributes($name, $dob, $gender, $group, $phone, $serial, $overrides);
            $donor = Donor::withTrashed()->firstOrNew(['reference' => $reference]);
            $donor->fill($attributes);
            $donor->deleted_at = null;
            $donor->save();
            $donors[$index] = $donor;
        }

        return $donors;
    }

    private function donorAttributes(string $name, string $dob, string $gender, string $group, string $phone, int $serial, array $overrides): array
    {
        return [
            'full_name' => $name, 'date_of_birth' => $dob, 'gender' => $gender,
            'identity_document_type' => 'nrc', 'identity_number' => "12/LaMaNa(N){$serial}",
            'nrc_state' => '12', 'nrc_township' => 'LAMANA', 'nrc_type' => 'N', 'nrc_serial' => (string) $serial,
            'passport_number' => null, 'phone' => $phone, 'phone_normalized' => Donor::normalizePhone($phone),
            'email' => strtolower(str_replace(' ', '.', $name)).'@bloodcare.test',
            'address' => 'Demo address, Yangon, Myanmar', 'blood_group' => $group,
            'donation_type_preference' => 'whole_blood', 'emergency_contact' => '09 799 100 '.substr($phone, -3),
            'previous_donation' => false, 'health_notes' => null, 'consent_at' => now()->subDays(180),
            'status' => 'active', 'eligibility_status' => 'eligible', 'deferral_type' => 'none',
            'deferral_reason' => null, 'deferral_end_date' => null, 'last_donation_date' => null,
            'next_eligible_date' => null, 'staff_notes' => 'Current BloodCare demo dataset.', 'user_id' => null,
            ...$overrides,
        ];
    }

    /** @param array<string, Donor> $donors
     *  @param array<string, DonationCentre> $centres
     *  @return array<string, Appointment>
     */
    private function seedAppointments(array $donors, array $centres, User $staff): array
    {
        $definitions = [
            'a01' => ['APT-DEMO-V2-0001', 'd14', 'yangon', 14, '09:00', 'Donation', 'Public booking', 'pending'],
            'a02' => ['APT-DEMO-V2-0002', 'd11', 'north_okka', 21, '10:30', 'Donation', 'Staff', 'confirmed'],
            'a03' => ['APT-DEMO-V2-0003', 'd09', 'thingangyun', 0, '13:00', 'Eligibility review', 'Public booking', 'checked_in'],
            'a04' => ['APT-DEMO-V2-0004', 'd07', 'yangon', -2, '09:00', 'Donation', 'Staff', 'completed'],
            'a05' => ['APT-DEMO-V2-0005', 'd05', 'yangon', 10, '14:30', 'Consultation', 'Staff', 'cancelled'],
            'a06' => ['APT-DEMO-V2-0006', 'd08', 'north_okka', -7, '10:30', 'Donation', 'Public booking', 'no_show'],
            'a07' => ['APT-DEMO-V2-0007', 'd12', 'thingangyun', 0, '14:30', 'Donation', 'Staff', 'checked_in'],
        ];

        $appointments = [];
        foreach ($definitions as $key => [$reference, $donorKey, $centreKey, $day, $time, $purpose, $source, $status]) {
            $centre = $centres[$centreKey];
            $appointment = Appointment::withTrashed()->firstOrNew(['reference' => $reference]);
            $appointment->fill([
                'donor_id' => $donors[$donorKey]->id, 'donation_centre_id' => $centre->id,
                'centre_name' => $centre->name, 'appointment_date' => $this->date($day), 'appointment_time' => $time,
                'requested_region' => null, 'requested_township' => null, 'purpose' => $purpose, 'source' => $source,
                'status' => $status, 'notes' => "Current demo {$status} appointment.",
                'acknowledged_at' => now()->subDay(), 'handled_by' => $status === 'pending' ? null : $staff->id,
            ]);
            $appointment->deleted_at = null;
            $appointment->save();
            $appointments[$key] = $appointment;
        }

        $location = Appointment::withTrashed()->firstOrNew(['reference' => 'APT-DEMO-V2-0008']);
        $location->fill([
            'donor_id' => $donors['d11']->id, 'donation_centre_id' => null, 'centre_name' => null,
            'appointment_date' => null, 'appointment_time' => null, 'requested_region' => 'Shan State',
            'requested_township' => 'Taunggyi', 'purpose' => 'Donation', 'source' => 'Public booking',
            'status' => 'pending', 'notes' => 'Demo request for a new donation location.',
            'acknowledged_at' => now()->subDay(), 'handled_by' => null,
        ]);
        $location->deleted_at = null;
        $location->save();
        $appointments['a08'] = $location;

        return $appointments;
    }

    /** @param array<string, Donor> $donors */
    private function seedCards(array $donors, User $admin): void
    {
        $definitions = [
            ['d01', 'CARD-DEMO-V2-0001', -180, 185, 'active'],
            ['d02', 'CARD-DEMO-V2-0002', -365, 365, 'suspended'],
            ['d03', 'CARD-DEMO-V2-0003', -220, 145, 'active'],
            ['d05', 'CARD-DEMO-V2-0005', -730, -30, 'active'],
            ['d07', 'CARD-DEMO-V2-0007', -120, 245, 'active'],
            ['d08', 'CARD-DEMO-V2-0008', -200, 165, 'active'],
            ['d09', 'CARD-DEMO-V2-0009', null, null, 'pending'],
            ['d12', 'CARD-DEMO-V2-0012', null, null, 'pending'],
        ];

        foreach ($definitions as [$donorKey, $number, $issuedDay, $expiresDay, $status]) {
            DonationCard::updateOrCreate(['donor_id' => $donors[$donorKey]->id], [
                'card_number' => $number, 'issued_at' => $issuedDay === null ? null : $this->date($issuedDay),
                'expires_at' => $expiresDay === null ? null : $this->date($expiresDay), 'status' => $status,
                'qr_token' => 'bloodcare-demo-v2-'.strtolower(str_replace('CARD-DEMO-V2-', '', $number)),
                'replacement_count' => $status === 'suspended' ? 1 : 0, 'print_count' => $issuedDay === null ? 0 : 2,
                'issued_by' => $issuedDay === null ? null : $admin->id, 'notes' => "Current demo {$status} donation card.",
            ]);
        }
    }

    /** @param array<string, Donor> $donors
     *  @param array<string, DonationCentre> $centres
     *  @param array<string, Appointment> $appointments
     *  @return array<string, Donation>
     */
    private function seedDonations(array $donors, array $centres, array $appointments, User $staff): array
    {
        $definitions = [
            'q01' => ['DON-DEMO-V2-Q01', 'd01', 'yangon', null, 0, 'passed', 'accepted', 'BU-DEMO-V2-Q01'],
            'rel_a_neg' => ['DON-DEMO-V2-REL01', 'd02', 'north_okka', null, -3, 'passed', 'accepted', 'BU-DEMO-V2-REL01'],
            'discard' => ['DON-DEMO-V2-DISC01', 'd03', 'thingangyun', null, -2, 'passed', 'accepted', 'BU-DEMO-V2-DISC01'],
            'allocated' => ['DON-DEMO-V2-ALLOC01', 'd04', 'yangon', null, -2, 'passed', 'accepted', 'BU-DEMO-V2-ALLOC01'],
            'rejected' => ['DON-DEMO-V2-REJ01', 'd05', 'yangon', null, -5, 'failed', 'rejected', null],
            'transfused' => ['DON-DEMO-V2-TX01', 'd06', 'north_okka', null, -1, 'passed', 'accepted', 'BU-DEMO-V2-TX01'],
            'components_full' => ['DON-DEMO-V2-CMP01', 'd07', 'yangon', 'a04', -2, 'passed', 'accepted', 'BU-DEMO-V2-CMP01'],
            'rel_o_neg' => ['DON-DEMO-V2-REL02', 'd08', 'thingangyun', null, -1, 'passed', 'accepted', 'BU-DEMO-V2-REL02'],
            'screening' => ['DON-DEMO-V2-SCR01', 'd09', 'thingangyun', 'a03', 0, 'pending', 'screening', 'BAG-DEMO-V2-SCR01'],
            'components_partial' => ['DON-DEMO-V2-CMP02', 'd13', 'mandalay', null, 0, 'passed', 'accepted', 'BU-DEMO-V2-CMP02'],
        ];

        $donations = [];
        foreach ($definitions as $key => [$reference, $donorKey, $centreKey, $appointmentKey, $day, $screening, $status, $bag]) {
            $donationDate = Carbon::parse($this->date($day));
            $donations[$key] = Donation::updateOrCreate(['reference' => $reference], [
                'donor_id' => $donors[$donorKey]->id, 'appointment_id' => $appointmentKey ? $appointments[$appointmentKey]->id : null,
                'donation_centre_id' => $centres[$centreKey]->id, 'donation_date' => $donationDate->toDateString(),
                'quantity_ml' => 450, 'blood_group' => $donors[$donorKey]->blood_group, 'donation_type' => 'whole_blood',
                'screening_result' => $screening, 'status' => $status, 'bag_unit_number' => $bag,
                'expires_at' => $status === 'rejected' ? null : $donationDate->copy()->addDays(42)->toDateString(),
                'storage_location' => $status === 'rejected' ? null : 'Demo Cold Room / WB Rack',
                'screening_notes' => "Current demo {$status} donation.", 'recorded_by' => $staff->id,
            ]);
        }

        for ($index = 1; $index <= 3; $index++) {
            $key = 'history'.$index;
            $date = today()->subDays(120 * $index);
            $donations[$key] = Donation::updateOrCreate(['reference' => 'DON-DEMO-V2-H'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)], [
                'donor_id' => $donors['d11']->id, 'appointment_id' => null, 'donation_centre_id' => $centres['yangon']->id,
                'donation_date' => $date, 'quantity_ml' => 450, 'blood_group' => 'A+', 'donation_type' => 'whole_blood',
                'screening_result' => 'passed', 'status' => 'accepted', 'bag_unit_number' => 'BU-DEMO-V2-H'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'expires_at' => $date->copy()->addDays(42), 'storage_location' => 'Demo Archive Rack',
                'screening_notes' => 'Completed historical donation.', 'recorded_by' => $staff->id,
            ]);
        }

        return $donations;
    }

    /** @param array<string, Donor> $donors
     *  @param array<string, DonationCentre> $centres
     *  @param array<string, Appointment> $appointments
     *  @param array<string, Donation> $donations
     *  @return array<string, DonorScreening>
     */
    private function seedScreenings(array $donors, array $centres, array $appointments, array $donations, User $staff): array
    {
        $definitions = [
            'q01' => ['SCR-DEMO-V2-Q01', 'd01', 'q01', null, 'yangon', 0, 'passed'],
            'rel_a_neg' => ['SCR-DEMO-V2-REL01', 'd02', 'rel_a_neg', null, 'north_okka', -3, 'passed'],
            'discard' => ['SCR-DEMO-V2-DISC01', 'd03', 'discard', null, 'thingangyun', -2, 'passed'],
            'allocated' => ['SCR-DEMO-V2-ALLOC01', 'd04', 'allocated', null, 'yangon', -2, 'passed'],
            'rejected' => ['SCR-DEMO-V2-REJ01', 'd05', 'rejected', null, 'yangon', -5, 'failed'],
            'transfused' => ['SCR-DEMO-V2-TX01', 'd06', 'transfused', null, 'north_okka', -1, 'passed'],
            'components_full' => ['SCR-DEMO-V2-CMP01', 'd07', 'components_full', 'a04', 'yangon', -2, 'passed'],
            'rel_o_neg' => ['SCR-DEMO-V2-REL02', 'd08', 'rel_o_neg', null, 'thingangyun', -1, 'passed'],
            'screening' => ['SCR-DEMO-V2-SCR01', 'd09', 'screening', 'a03', 'thingangyun', 0, 'pending'],
            'components_partial' => ['SCR-DEMO-V2-CMP02', 'd13', 'components_partial', null, 'mandalay', 0, 'passed'],
        ];

        $screenings = [];
        foreach ($definitions as $key => [$reference, $donorKey, $donationKey, $appointmentKey, $centreKey, $day, $outcome]) {
            $screenings[$key] = DonorScreening::updateOrCreate(['reference' => $reference], $this->screeningAttributes(
                $donors[$donorKey], $staff, $centres[$centreKey], today()->addDays($day)->setTime(8, 30), $outcome,
                $donations[$donationKey], $appointmentKey ? $appointments[$appointmentKey] : null,
            ));
        }

        $screenings['ready'] = DonorScreening::updateOrCreate(['reference' => 'SCR-DEMO-V2-READY01'], $this->screeningAttributes(
            $donors['d12'], $staff, $centres['thingangyun'], today()->setTime(8, 0), 'passed', null, $appointments['a07'],
        ));

        for ($index = 1; $index <= 3; $index++) {
            $key = 'history'.$index;
            $screenings[$key] = DonorScreening::updateOrCreate(['reference' => 'SCR-DEMO-V2-H'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)], $this->screeningAttributes(
                $donors['d11'], $staff, $centres['yangon'], Carbon::parse($donations[$key]->donation_date)->setTime(8, 0), 'passed', $donations[$key], null,
            ));
        }

        return $screenings;
    }

    private function screeningAttributes(Donor $donor, User $staff, DonationCentre $centre, Carbon $screenedAt, string $outcome, ?Donation $donation, ?Appointment $appointment): array
    {
        $failed = $outcome === 'failed';

        return [
            'donor_id' => $donor->id, 'appointment_id' => $appointment?->id, 'donation_id' => $donation?->id,
            'donation_centre_id' => $centre->id, 'screened_at' => $screenedAt,
            'next_screening_date' => $outcome === 'passed' ? $screenedAt->copy()->addDays(90)->toDateString() : null,
            'weight_kg' => 62.50, 'hemoglobin_level' => $failed ? 10.80 : 13.60,
            'systolic_blood_pressure' => 118, 'diastolic_blood_pressure' => 76, 'pulse_rate' => 72,
            'body_temperature_celsius' => 36.70, 'medication_flag' => false, 'current_medications' => null,
            'recent_travel_flag' => false, 'recent_travel_details' => null, 'high_risk_activity_flag' => false,
            'high_risk_activity_details' => null, 'outcome' => $outcome,
            'deferral_type' => $failed ? 'temporary' : 'none',
            'deferral_reason' => $failed ? 'Low haemoglobin; temporary deferral.' : null,
            'deferral_end_date' => $failed ? $this->date(30) : null,
            'notes' => "Current demo {$outcome} donor screening.", 'verified_by_staff_id' => $staff->id,
        ];
    }

    /** @param array<string, Donation> $donations
     *  @return array<string, BloodUnit>
     */
    private function seedBloodUnits(array $donations): array
    {
        $unitKeys = ['q01', 'rel_a_neg', 'discard', 'allocated', 'transfused', 'components_full', 'rel_o_neg', 'components_partial', 'history1', 'history2', 'history3'];
        $units = [];
        foreach ($unitKeys as $key) {
            $donation = $donations[$key];
            $units[$key] = BloodUnit::updateOrCreate(['unit_number' => $donation->bag_unit_number], [
                'donation_id' => $donation->id, 'parent_blood_unit_id' => null, 'blood_group' => $donation->blood_group,
                'component_type' => 'whole_blood', 'leukoreduced' => false, 'irradiated' => false, 'washed' => false,
                'collected_at' => $donation->donation_date, 'expires_at' => $donation->expires_at,
                'storage_location' => $key === 'history1' || $key === 'history2' || $key === 'history3' ? 'Demo Archive Rack' : 'Demo Cold Room / WB Rack',
                'status' => 'quarantined', 'released_at' => null, 'released_by' => null,
                'notes' => 'Awaiting or recording the deterministic Laboratory lifecycle for '.$donation->reference.'.',
            ]);
        }

        return $units;
    }

    /** @param array<string, Donation> $donations
     *  @param array<string, BloodUnit> $units
     *  @return array<string, LabTest>
     */
    private function seedLabTests(array $donations, array $units, User $labAdmin): array
    {
        $released = ['rel_a_neg', 'allocated', 'transfused', 'components_full', 'rel_o_neg', 'components_partial', 'history1', 'history2', 'history3'];
        $tests = [];

        foreach ($released as $key) {
            $donation = $donations[$key];
            $testedAt = Carbon::parse($donation->donation_date)->endOfDay();
            $tests[$key] = LabTest::updateOrCreate(['donation_id' => $donation->id], [
                'reference' => 'LAB-DEMO-V2-'.strtoupper($key),
                ...$this->negativeLabResults($donation->blood_group),
                'release_status' => 'released', 'notes' => 'All required demo safety checks acceptable.',
                'tested_by' => $labAdmin->id, 'tested_at' => $testedAt,
                'released_at' => $testedAt, 'released_by' => $labAdmin->id,
            ]);
            $units[$key]->update([
                'status' => str_starts_with($key, 'history') ? 'used' : 'available',
                'released_at' => $testedAt, 'released_by' => $labAdmin->id,
                'notes' => 'Laboratory released demo unit.',
            ]);
        }

        $donation = $donations['discard'];
        $testedAt = Carbon::parse($donation->donation_date)->endOfDay();
        $tests['discard'] = LabTest::updateOrCreate(['donation_id' => $donation->id], [
            'reference' => 'LAB-DEMO-V2-DISCARD',
            ...$this->negativeLabResults($donation->blood_group),
            'hepatitis_b_status' => 'reactive', 'release_status' => 'discarded',
            'notes' => 'Reactive Hepatitis B demo result; unit permanently discarded.',
            'tested_by' => $labAdmin->id, 'tested_at' => $testedAt, 'released_at' => null, 'released_by' => null,
        ]);
        $units['discard']->update([
            'status' => 'discarded', 'released_at' => null, 'released_by' => null,
            'notes' => 'Laboratory safety lock: reactive Hepatitis B demo result.',
        ]);

        // q01 intentionally has no LabTest row: this is the one genuine item in
        // the Quarantined queue that Lab users can edit and finalize themselves.
        LabTest::where('donation_id', $donations['q01']->id)->delete();
        $units['q01']->update(['status' => 'quarantined', 'released_at' => null, 'released_by' => null]);

        return $tests;
    }

    private function negativeLabResults(string $bloodGroup): array
    {
        return [
            'hiv_status' => 'negative', 'hepatitis_b_status' => 'negative', 'hepatitis_c_status' => 'negative',
            'syphilis_status' => 'negative', 'confirmed_blood_group' => $bloodGroup,
            'rhd_type' => str_ends_with($bloodGroup, '+') ? 'positive' : 'negative',
            'antibody_screen_status' => 'negative', 'htlv_status' => 'not_required',
            'malaria_status' => 'negative', 'chagas_status' => 'not_required',
            'west_nile_status' => 'not_required', 'zika_status' => 'not_required',
        ];
    }

    /** @param array<string, BloodUnit> $units
     *  @return array<string, BloodUnit>
     */
    private function seedComponents(array $units, User $labAdmin): array
    {
        $components = [];
        $parent = $units['components_full']->fresh();
        $components['rbc_early'] = $this->component($parent, 'BU-DEMO-V2-CMP01-RBC', 'red_cells', 42, $labAdmin, true, true, false);
        $plasma = $this->component($parent, 'BU-DEMO-V2-CMP01-PLS', 'plasma', 365, $labAdmin, false, false, false);
        $components['platelets'] = $this->component($parent, 'BU-DEMO-V2-CMP01-PLT', 'platelets', 5, $labAdmin, true, false, false);
        $components['cryo'] = BloodUnit::updateOrCreate(['unit_number' => 'BU-DEMO-V2-CMP01-CRYO'], [
            'donation_id' => $parent->donation_id, 'parent_blood_unit_id' => $plasma->id,
            'blood_group' => $parent->blood_group, 'component_type' => 'cryoprecipitate',
            'leukoreduced' => false, 'irradiated' => false, 'washed' => false,
            'collected_at' => $parent->collected_at, 'expires_at' => $parent->collected_at->copy()->addDays(365),
            'storage_location' => 'Demo Component Freezer / CRYO-1', 'status' => 'available',
            'released_at' => $parent->released_at, 'released_by' => $labAdmin->id,
            'notes' => 'Cryoprecipitate correctly derived from demo Plasma.',
        ]);
        $plasma->update(['status' => 'used', 'notes' => 'Used as source Plasma for BU-DEMO-V2-CMP01-CRYO.']);
        $parent->update(['status' => 'used', 'notes' => 'Fully processed into Red Cells, Plasma and Platelets.']);

        $partial = $units['components_partial']->fresh();
        $components['rbc_late'] = $this->component($partial, 'BU-DEMO-V2-CMP02-RBC', 'red_cells', 42, $labAdmin, true, false, true);
        $partial->update(['status' => 'processing', 'notes' => 'Partially processed: Red Cells prepared; Plasma and Platelets remain available for preparation.']);

        return $components;
    }

    private function component(BloodUnit $parent, string $number, string $type, int $expiryDays, User $labAdmin, bool $leukoreduced, bool $irradiated, bool $washed): BloodUnit
    {
        return BloodUnit::updateOrCreate(['unit_number' => $number], [
            'donation_id' => $parent->donation_id, 'parent_blood_unit_id' => $parent->id,
            'blood_group' => $parent->blood_group, 'component_type' => $type,
            'leukoreduced' => $leukoreduced, 'irradiated' => $irradiated, 'washed' => $washed,
            'collected_at' => $parent->collected_at, 'expires_at' => $parent->collected_at->copy()->addDays($expiryDays),
            'storage_location' => 'Demo Component Storage / '.strtoupper($type), 'status' => 'available',
            'released_at' => $parent->released_at, 'released_by' => $labAdmin->id,
            'notes' => 'Demo component prepared from '.$parent->unit_number.'.',
        ]);
    }

    /** @param array<string, Hospital> $hospitals
     *  @param array<string, User> $users
     *  @param array<string, BloodUnit> $units
     *  @param array<string, BloodUnit> $components
     *  @return array<string, BloodRequest>
     */
    private function seedHospitalRequests(array $hospitals, array $users, array $units, array $components): array
    {
        $requests = [];
        $requests['pending'] = $this->request('REQ-DEMO-V2-0001', $hospitals['yangon'], $users['hospital_yangon'], [
            'patient_reference' => 'CASE-DEMO-1001', 'blood_group' => 'A-', 'component_type' => 'whole_blood',
            'quantity' => 1, 'priority' => 'routine', 'status' => 'pending', 'clinical_note' => 'Routine anaemia support demo request.',
        ]);
        $requests['approved'] = $this->request('REQ-DEMO-V2-0002', $hospitals['yangon'], $users['hospital_yangon'], [
            'patient_reference' => 'CASE-DEMO-1002', 'blood_group' => 'O+', 'component_type' => 'red_cells',
            'requires_leukoreduced' => true, 'quantity' => 1, 'priority' => 'urgent', 'status' => 'approved',
            'clinical_note' => 'Use this request to demonstrate FEFO across the two O+ leukoreduced Red Cell units.',
            'reviewed_by' => $users['staff']->id, 'reviewed_at' => now()->subHours(2), 'decision_note' => 'Approved for compatible released stock.',
        ]);
        $requests['allocated'] = $this->request('REQ-DEMO-V2-0003', $hospitals['mandalay'], $users['hospital_mandalay'], [
            'patient_reference' => 'CASE-DEMO-1003', 'blood_group' => 'B-', 'component_type' => 'whole_blood',
            'quantity' => 1, 'priority' => 'urgent', 'status' => 'allocated', 'clinical_note' => 'Allocated but not yet dispatched.',
            'reviewed_by' => $users['staff']->id, 'reviewed_at' => now()->subHours(4), 'decision_note' => 'Approved.',
        ]);
        $requests['transfused'] = $this->request('REQ-DEMO-V2-0004', $hospitals['mandalay'], $users['hospital_mandalay'], [
            'patient_reference' => 'CASE-DEMO-1004', 'blood_group' => 'AB-', 'component_type' => 'whole_blood',
            'quantity' => 1, 'priority' => 'emergency', 'status' => 'transfused', 'clinical_note' => 'Completed transfusion with haemovigilance example.',
            'reviewed_by' => $users['staff']->id, 'reviewed_at' => now()->subDay(), 'decision_note' => 'Emergency request approved.',
        ]);
        $requests['rejected'] = $this->request('REQ-DEMO-V2-0005', $hospitals['yangon'], $users['hospital_yangon'], [
            'patient_reference' => 'CASE-DEMO-1005', 'blood_group' => 'AB+', 'component_type' => 'plasma',
            'quantity' => 2, 'priority' => 'routine', 'status' => 'rejected', 'clinical_note' => 'Rejected request example.',
            'reviewed_by' => $users['staff']->id, 'reviewed_at' => now()->subHours(6), 'decision_note' => 'Clinical details incomplete (demo).',
        ]);

        $allocatedUnit = $units['allocated']->fresh();
        $allocation = BloodAllocation::updateOrCreate(['blood_unit_id' => $allocatedUnit->id], [
            'blood_request_id' => $requests['allocated']->id, 'crossmatch_result' => 'compatible', 'status' => 'allocated',
            'allocated_by' => $users['staff']->id, 'allocated_at' => now()->subHours(3),
            'dispatched_at' => null, 'received_at' => null, 'transfused_at' => null,
            'notes' => 'Compatible cross-match; reserved for Mandalay demo request.',
        ]);
        $allocatedUnit->update(['status' => 'reserved']);

        $transfusedUnit = $units['transfused']->fresh();
        $transfusion = BloodAllocation::updateOrCreate(['blood_unit_id' => $transfusedUnit->id], [
            'blood_request_id' => $requests['transfused']->id, 'crossmatch_result' => 'compatible', 'status' => 'transfused',
            'allocated_by' => $users['staff']->id, 'allocated_at' => now()->subDay(),
            'dispatched_at' => now()->subHours(20), 'received_at' => now()->subHours(18), 'transfused_at' => now()->subHours(16),
            'notes' => 'Completed hospital lifecycle demo.',
        ]);
        $transfusedUnit->update(['status' => 'used']);
        AdverseReaction::updateOrCreate(['reference' => 'HVR-DEMO-V2-0001'], [
            'blood_allocation_id' => $transfusion->id, 'hospital_id' => $hospitals['mandalay']->id,
            'reported_by' => $users['hospital_mandalay']->id, 'severity' => 'moderate', 'status' => 'investigating',
            'suspected_reaction_type' => 'febrile_non_hemolytic', 'reaction_type' => 'febrile_non_hemolytic',
            'imputability' => 'possible', 'outcome' => 'recovered',
            'symptoms' => 'Fever and chills during the demonstration transfusion scenario.',
            'action_taken' => 'Transfusion stopped and patient reviewed by the clinical team (demo).',
            'investigation_notes' => 'Unit identity, cross-match and transfusion timestamps reviewed; demonstration investigation remains open.',
            'corrective_action' => 'Continue observation and complete the BloodCare haemovigilance review before closure.',
            'reviewed_by' => $users['staff']->id, 'reviewed_at' => now()->subHours(12),
            'closed_by' => null, 'closed_at' => null,
            'occurred_at' => now()->subHours(15),
        ]);

        // The approved O+ request is intentionally left unallocated. Both
        // rbc_early and rbc_late are released, compatible, leukoreduced and
        // available; FEFO should recommend rbc_early because it expires first.
        $components['rbc_early']->update(['status' => 'available']);
        $components['rbc_late']->update(['status' => 'available']);

        return $requests;
    }

    private function request(string $reference, Hospital $hospital, User $requester, array $attributes): BloodRequest
    {
        return BloodRequest::updateOrCreate(['reference' => $reference], [
            'hospital_id' => $hospital->id, 'requested_by' => $requester->id,
            'requires_leukoreduced' => false, 'requires_irradiated' => false, 'requires_washed' => false,
            'decision_note' => null, 'reviewed_by' => null, 'reviewed_at' => null,
            ...$attributes,
        ]);
    }

    /** @param array<string, User> $users
     *  @param array<string, Donor> $donors
     *  @param array<string, Appointment> $appointments
     *  @param array<string, Donation> $donations
     *  @param array<string, DonorScreening> $screenings
     *  @param array<string, LabTest> $labTests
     *  @param array<string, BloodRequest> $requests
     */
    private function seedActivityLogs(array $users, array $donors, array $appointments, array $donations, array $screenings, array $labTests, array $requests): void
    {
        $definitions = [
            ['HIS-DEMO-V2-0001', 'Donor', 'Public donor registration submitted', $donors['d09'], 'd09', null, 'Pending', 'public-registration'],
            ['HIS-DEMO-V2-0002', 'Appointment', 'Public appointment booked', $appointments['a01'], 'd14', null, 'Pending', 'public-booking'],
            ['HIS-DEMO-V2-0003', 'Screening', 'Donor medical screening recorded', $screenings['ready'], 'd12', 'staff', 'Passed', 'admin-donor-screening'],
            ['HIS-DEMO-V2-0004', 'Donation', 'Donation accepted into quarantine', $donations['q01'], 'd01', 'staff', 'Quarantined', 'admin-donations'],
            ['HIS-DEMO-V2-0005', 'Laboratory', 'Mandatory donation screening released', $labTests['components_full'], 'd07', 'lab_admin', 'Released', 'admin-laboratory'],
            ['HIS-DEMO-V2-0006', 'Laboratory', 'Reactive donation screening discarded', $labTests['discard'], 'd03', 'lab_admin', 'Discarded', 'admin-laboratory'],
            ['HIS-DEMO-V2-0007', 'Hospital Request', 'Hospital blood request submitted', $requests['pending'], null, 'hospital_yangon', 'Pending', 'hospital-portal'],
            ['HIS-DEMO-V2-0008', 'Hospital Request', 'Safe blood unit allocated', $requests['allocated'], null, 'staff', 'Allocated', 'admin-hospital-requests'],
            ['HIS-DEMO-V2-0009', 'Transfusion', 'Hospital recorded transfusion', $requests['transfused'], null, 'hospital_mandalay', 'Transfused', 'hospital-portal'],
            ['HIS-DEMO-V2-0010', 'User', 'Staff registration awaiting approval', $users['pending_staff'], null, 'admin', 'Pending', 'admin-users'],
        ];

        foreach ($definitions as $offset => [$reference, $type, $action, $subject, $donorKey, $userKey, $result, $source]) {
            $log = ActivityLog::updateOrCreate(['reference' => $reference], [
                'type' => $type, 'action' => $action, 'subject_type' => get_class($subject), 'subject_id' => $subject->id,
                'donor_id' => $donorKey ? $donors[$donorKey]->id : null, 'user_id' => $userKey ? $users[$userKey]->id : null,
                'result' => $result, 'details' => "Current deterministic demo history entry for {$type}.",
                'source' => $source, 'metadata' => ['demo' => true, 'dataset' => 'v2', 'seed_reference' => $reference],
            ]);
            $log->forceFill(['created_at' => now()->subMinutes(60 - ($offset * 5))])->save();
        }
    }

    /** @param array<string, User> $users */
    private function seedNotifications(array $users): void
    {
        $definitions = [
            ['00000000-0000-4000-8000-000000000201', 'admin', 'hospital_request', 'warning', ['reference' => 'REQ-DEMO-V2-0001', 'hospital' => 'Yangon General Hospital (Demo)', 'group' => 'A-', 'component' => 'whole_blood', 'quantity' => 1, 'priority' => 'routine'], 'bloodcare.admin.blood-requests', null],
            ['00000000-0000-4000-8000-000000000202', 'lab_admin', 'unit_quarantined', 'warning', ['unit' => 'BU-DEMO-V2-Q01', 'group' => 'A+', 'donation' => 'DON-DEMO-V2-Q01'], 'bloodcare.lab.laboratory', null],
            ['00000000-0000-4000-8000-000000000203', 'lab_staff', 'unit_quarantined', 'warning', ['unit' => 'BU-DEMO-V2-Q01', 'group' => 'A+', 'donation' => 'DON-DEMO-V2-Q01'], 'bloodcare.lab.laboratory', now()->subHour()],
            ['00000000-0000-4000-8000-000000000204', 'hospital_yangon', 'request_approved', 'success', ['reference' => 'REQ-DEMO-V2-0002'], 'hospital.dashboard', null],
            ['00000000-0000-4000-8000-000000000205', 'hospital_mandalay', 'unit_dispatched', 'success', ['reference' => 'REQ-DEMO-V2-0004', 'unit' => 'BU-DEMO-V2-TX01'], 'hospital.dashboard', now()->subHours(18)],
        ];

        foreach ($definitions as $offset => [$id, $userKey, $event, $level, $parameters, $routeName, $readAt]) {
            DB::table('notifications')->updateOrInsert(['id' => $id], [
                'type' => BloodCareWorkflowNotification::class, 'notifiable_type' => User::class,
                'notifiable_id' => $users[$userKey]->id,
                'data' => json_encode([
                    'event' => $event, 'level' => $level,
                    'title_key' => "bloodcare.notifications.events.{$event}.title",
                    'message_key' => "bloodcare.notifications.events.{$event}.message",
                    'parameters' => $parameters, 'route_name' => $routeName,
                ], JSON_THROW_ON_ERROR),
                'read_at' => $readAt, 'created_at' => now()->subMinutes(25 - ($offset * 3)), 'updated_at' => now(),
            ]);
        }
    }

    private function date(int $daysFromToday): string
    {
        return today()->addDays($daysFromToday)->toDateString();
    }
}
