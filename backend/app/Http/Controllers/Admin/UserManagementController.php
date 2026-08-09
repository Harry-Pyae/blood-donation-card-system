<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['User', 'System Staff', 'System Admin', 'Lab Staff', 'Lab Admin', 'Staff', 'Admin', 'Lab'])],
        ]);
        $newRole = $this->databaseRole($data['role']);
        $this->protectAccount($user, $newRole, (bool) $user->is_banned);

        $oldRole = $user->role;
        $user->update(['role' => $newRole]);
        $this->log($user, 'User role changed', "{$oldRole} → {$user->role}");

        return response()->json(['message' => 'User role updated.']);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['Active', 'Banned'])],
        ]);
        $isBanned = $data['status'] === 'Banned';
        $this->protectAccount($user, (string) $user->role, $isBanned);

        DB::transaction(function () use ($user, $isBanned): void {
            $user->update(['is_banned' => $isBanned]);
            if ($isBanned) {
                $this->removeSessions($user);
            }
            $this->log($user, $isBanned ? 'User account banned' : 'User account restored');
        });

        return response()->json(['message' => 'User status updated.']);
    }

    public function updateApproval(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'approval' => ['required', Rule::in(['Approved', 'Rejected'])],
            'role' => ['nullable', Rule::in(['System Staff', 'System Admin', 'Lab Staff', 'Lab Admin', 'Staff', 'Admin', 'Lab'])],
        ]);
        $approval = strtolower($data['approval']);
        $requestedRole = isset($data['role']) ? $this->databaseRole($data['role']) : (string) $user->role;
        $this->protectAccount(
            $user,
            $requestedRole,
            (bool) $user->is_banned,
            $approval,
        );

        DB::transaction(function () use ($user, $approval, $requestedRole): void {
            $approved = $approval === User::APPROVAL_APPROVED;
            $oldRole = (string) $user->role;
            $updates = [
                'approval_status' => $approval,
                'approved_at' => $approved ? now() : null,
                'approved_by' => backpack_user()?->id,
            ];

            if ($approved) {
                $updates['role'] = in_array($requestedRole, [
                    User::ROLE_STAFF,
                    User::ROLE_ADMIN,
                    User::ROLE_LAB,
                    User::ROLE_LAB_STAFF,
                    User::ROLE_LAB_ADMIN,
                ], true)
                    ? $requestedRole
                    : User::ROLE_STAFF;
            }

            $user->update($updates);

            if (! $approved) {
                $this->removeSessions($user);
            }

            $this->log(
                $user,
                $approved ? 'Staff registration approved' : 'Staff registration rejected',
                $approved
                    ? "approval {$approval}; role {$oldRole} → {$user->role}"
                    : "approval {$approval}",
            );
        });

        return response()->json([
            'message' => $approval === User::APPROVAL_APPROVED
                ? 'Staff registration approved.'
                : 'Staff registration rejected.',
            'role' => $user->fresh()->role,
        ]);
    }

    private function protectAccount(
        User $user,
        string $newRole,
        bool $willBeBanned = false,
        ?string $newApprovalStatus = null,
    ): void
    {
        if ((int) backpack_user()?->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'account' => 'You cannot change the role, approval or status of your current session.',
            ]);
        }

        $removesActiveAdmin = $user->role === User::ROLE_ADMIN
            && ! $user->is_banned
            && $user->approval_status === User::APPROVAL_APPROVED
            && (
                $newRole !== User::ROLE_ADMIN
                || $willBeBanned
                || ($newApprovalStatus !== null && $newApprovalStatus !== User::APPROVAL_APPROVED)
            );

        if ($removesActiveAdmin && User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('approval_status', User::APPROVAL_APPROVED)
            ->where('is_banned', false)
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'account' => 'At least one active System Administrator must remain.',
            ]);
        }
    }

    private function databaseRole(string $role): string
    {
        return match ($role) {
            'System Staff', 'Staff' => User::ROLE_STAFF,
            'System Admin', 'Admin' => User::ROLE_ADMIN,
            'Lab Staff' => User::ROLE_LAB_STAFF,
            'Lab Admin', 'Lab' => User::ROLE_LAB_ADMIN,
            default => User::ROLE_USER,
        };
    }

    private function removeSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function log(User $user, string $action, ?string $change = null): void
    {
        $status = $user->is_banned
            ? 'Banned'
            : ucfirst((string) ($user->approval_status ?? User::APPROVAL_APPROVED));

        ActivityLog::record([
            'type' => 'User',
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => backpack_user()?->id,
            'result' => $status,
            'details' => "{$user->email}; role {$user->role}".($change ? "; {$change}" : ''),
            'source' => 'admin-users',
        ]);
    }
}
