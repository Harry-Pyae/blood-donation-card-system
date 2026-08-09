<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\DonationCentre;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AppointmentManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $appointment = DB::transaction(function () use ($data): Appointment {
            $appointment = Appointment::create($this->attributes($data, null));
            $this->log($appointment, 'Appointment created by staff');

            return $appointment;
        });

        return response()->json(['message' => 'Appointment saved.', 'appointment' => $appointment], 201);
    }

    public function update(Request $request, string $appointment): JsonResponse
    {
        $model = Appointment::where('reference', $appointment)->firstOrFail();
        $data = $this->validated($request, true);

        DB::transaction(function () use ($model, $data): void {
            $model->update($this->attributes($data, $model));
            $this->log($model, 'Appointment updated by staff');
        });

        return response()->json(['message' => 'Appointment updated.', 'appointment' => $model->fresh()]);
    }

    public function updateStatus(Request $request, string $appointment): JsonResponse
    {
        $model = Appointment::where('reference', $appointment)->firstOrFail();
        $data = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'Confirmed', 'Checked in', 'Completed', 'Cancelled', 'No-show'])],
        ]);

        DB::transaction(function () use ($model, $data): void {
            $model->update([
                'status' => str($data['status'])->lower()->replace([' ', '-'], '_')->toString(),
                'handled_by' => backpack_user()?->id,
            ]);
            $this->log($model, 'Appointment status changed');
        });

        return response()->json(['message' => 'Appointment status updated.']);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'donorId' => ['required', 'string', Rule::exists('donors', 'reference')],
            'date' => ['required', 'date', $updating ? 'after_or_equal:today' : 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', Rule::in(['Donation', 'Eligibility review', 'Consultation'])],
            'centre' => ['required', 'string', Rule::exists('donation_centres', 'name')->where('is_active', true)],
            'status' => ['required', Rule::in(['Pending', 'Confirmed', 'Checked in', 'Completed', 'Cancelled', 'No-show'])],
            'source' => ['required', Rule::in(['Staff', 'Public booking'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function attributes(array $data, ?Appointment $appointment): array
    {
        $donor = Donor::where('reference', $data['donorId'])->firstOrFail();
        $centre = DonationCentre::where('name', $data['centre'])->firstOrFail();

        return [
            'reference' => $appointment?->reference ?? Appointment::generateReference(),
            'donor_id' => $donor->id,
            'donation_centre_id' => $centre->id,
            'centre_name' => $centre->name,
            'appointment_date' => $data['date'],
            'appointment_time' => $data['time'],
            'purpose' => $data['purpose'],
            'source' => $data['source'],
            'status' => str($data['status'])->lower()->replace([' ', '-'], '_')->toString(),
            'notes' => $data['notes'] ?: null,
            'acknowledged_at' => $appointment?->acknowledged_at ?? now(),
            'handled_by' => backpack_user()?->id,
        ];
    }

    private function log(Appointment $appointment, string $action): void
    {
        ActivityLog::record([
            'type' => 'Appointment',
            'action' => $action,
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'donor_id' => $appointment->donor_id,
            'user_id' => backpack_user()?->id,
            'result' => str($appointment->status)->replace('_', ' ')->title()->toString(),
            'details' => "{$appointment->reference}; {$appointment->centre_name}; {$appointment->appointment_date}",
            'source' => 'admin-appointments',
        ]);
    }
}
