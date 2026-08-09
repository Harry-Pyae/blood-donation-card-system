<?php

namespace App\Services;

use App\Models\AdverseReaction;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\User;
use App\Notifications\BloodCareWorkflowNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class BloodCareNotificationService
{
    public function staffRegistrationSubmitted(User $staff): void
    {
        $this->send($this->systemAdministrators(), 'staff_registration', 'info', [
            'name' => $staff->name,
            'email' => $staff->email,
        ], 'bloodcare.admin.users', [], ['q' => $staff->email]);
    }

    public function donationQuarantined(Donation $donation, BloodUnit $unit): void
    {
        // Laboratory notifications deliberately use operational references,
        // never donor identity/contact information.
        $this->send($this->laboratoryUsers(), 'unit_quarantined', 'warning', [
            'unit' => $unit->unit_number,
            'group' => $unit->blood_group,
            'donation' => $donation->reference,
        ], 'bloodcare.lab.laboratory', [], ['q' => $unit->unit_number]);
    }

    public function laboratoryDecision(Donation $donation, BloodUnit $unit, string $release): void
    {
        $event = $release === 'released' ? 'unit_released' : 'unit_discarded';
        $level = $release === 'released' ? 'success' : 'danger';

        $this->send($this->systemUsers(), $event, $level, [
            'unit' => $unit->unit_number,
            'group' => $unit->blood_group,
            'donation' => $donation->reference,
        ], 'bloodcare.admin.inventory', [], ['unit' => $unit->unit_number]);
    }

    public function hospitalRequestSubmitted(BloodRequest $request): void
    {
        $this->send($this->systemUsers(), 'hospital_request', $this->requestLevel($request), [
            'reference' => $request->reference,
            'hospital' => $request->hospital?->name ?? '—',
            'group' => $request->blood_group,
            'component' => $request->component_type,
            'quantity' => $request->quantity,
            'priority' => $request->priority,
        ], 'bloodcare.admin.blood-requests', [], ['q' => $request->reference]);
    }

    public function hospitalRequestReviewed(BloodRequest $request): void
    {
        $event = $request->status === 'approved' ? 'request_approved' : 'request_rejected';
        $level = $request->status === 'approved' ? 'success' : 'danger';

        $this->sendToUser($request->requester, $event, $level, [
            'reference' => $request->reference,
        ], 'hospital.dashboard');
    }

    public function unitAllocated(BloodRequest $request, BloodUnit $unit): void
    {
        $this->sendToUser($request->requester, 'unit_allocated', 'info', [
            'reference' => $request->reference,
            'unit' => $unit->unit_number,
        ], 'hospital.dashboard');
    }

    public function unitDispatched(BloodRequest $request, BloodUnit $unit): void
    {
        $this->sendToUser($request->requester, 'unit_dispatched', 'success', [
            'reference' => $request->reference,
            'unit' => $unit->unit_number,
        ], 'hospital.dashboard');
    }

    public function adverseReactionReported(AdverseReaction $reaction): void
    {
        $reaction->loadMissing(['hospital', 'allocation.unit']);
        $parameters = [
            'reference' => $reaction->reference,
            'hospital' => $reaction->hospital?->name ?? '—',
            'unit' => $reaction->allocation?->unit?->unit_number ?? '—',
            'severity' => str($reaction->severity)->replace('_', ' ')->title()->toString(),
        ];
        $level = in_array($reaction->severity, ['severe', 'life_threatening'], true) ? 'danger' : 'warning';

        $this->send(
            $this->systemUsers(),
            'reaction_reported',
            $level,
            $parameters,
            'bloodcare.admin.haemovigilance.show',
            ['reaction' => $reaction->getRouteKey()],
        );
        // Laboratory recipients receive only operational references/severity;
        // patient/case identifiers, symptoms and treatment details stay out.
        $this->send(
            $this->laboratoryUsers(),
            'reaction_reported',
            $level,
            $parameters,
            'bloodcare.lab.inventory',
            [],
            ['unit' => $reaction->allocation?->unit?->unit_number],
        );
    }

    public function haemovigilanceUpdated(AdverseReaction $reaction): void
    {
        $reaction->loadMissing('hospital');
        $this->send($this->hospitalUsers($reaction), 'haemovigilance_updated', $reaction->status === 'closed' ? 'success' : 'info', [
            'reference' => $reaction->reference,
            'status' => str($reaction->status)->replace('_', ' ')->title()->toString(),
        ], 'hospital.dashboard');
    }

    /** @param Collection<int, User> $recipients
     *  @param array<string, scalar|null> $parameters
     */
    private function send(
        Collection $recipients,
        string $event,
        string $level,
        array $parameters,
        ?string $routeName,
        array $routeParameters = [],
        array $routeQuery = [],
        ?string $routeFragment = null,
    ): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BloodCareWorkflowNotification(
            $event,
            $level,
            $parameters,
            $routeName,
            $routeParameters,
            array_filter($routeQuery, fn ($value) => $value !== null && $value !== ''),
            $routeFragment,
        ));
    }

    /** @param array<string, scalar|null> $parameters */
    private function sendToUser(?User $user, string $event, string $level, array $parameters, ?string $routeName): void
    {
        if (! $user || (bool) $user->is_banned) {
            return;
        }

        $user->notify(new BloodCareWorkflowNotification($event, $level, $parameters, $routeName));
    }

    /** @return Collection<int, User> */
    private function systemUsers(): Collection
    {
        return $this->approvedUsers([User::ROLE_ADMIN, User::ROLE_STAFF], true);
    }

    /** @return Collection<int, User> */
    private function systemAdministrators(): Collection
    {
        return $this->approvedUsers([User::ROLE_ADMIN], true);
    }

    /** @return Collection<int, User> */
    private function laboratoryUsers(): Collection
    {
        return $this->approvedUsers([User::ROLE_LAB, User::ROLE_LAB_STAFF, User::ROLE_LAB_ADMIN]);
    }

    /** @return Collection<int, User> */
    private function hospitalUsers(AdverseReaction $reaction): Collection
    {
        return User::query()
            ->where('hospital_id', $reaction->hospital_id)
            ->where('role', User::ROLE_HOSPITAL)
            ->where('approval_status', User::APPROVAL_APPROVED)
            ->where('is_banned', false)
            ->get();
    }

    /**
     * @param array<int, string> $roles
     * @return Collection<int, User>
     */
    private function approvedUsers(array $roles, bool $includeBootstrapAdministrator = false): Collection
    {
        return User::query()
            ->where('is_banned', false)
            ->where(function ($query) use ($roles, $includeBootstrapAdministrator): void {
                $query->where(function ($roleQuery) use ($roles): void {
                    $roleQuery->whereIn('role', $roles)
                        ->where('approval_status', User::APPROVAL_APPROVED);
                });

                if ($includeBootstrapAdministrator) {
                    $query->orWhere(function ($bootstrap): void {
                        $bootstrap->where(function ($role): void {
                            $role->whereNull('role')->orWhere('role', '');
                        })->where(function ($approval): void {
                            $approval->whereNull('approval_status')->orWhere('approval_status', '');
                        });
                    });
                }
            })
            ->get();
    }

    private function requestLevel(BloodRequest $request): string
    {
        return match ($request->priority) {
            'emergency' => 'danger',
            'urgent' => 'warning',
            default => 'info',
        };
    }
}
