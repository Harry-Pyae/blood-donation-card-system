<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'phone', 'job_title', 'workplace', 'hospital_id', 'password', 'role', 'approval_status', 'approved_at', 'approved_by', 'registration_note', 'is_banned'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_last_used_step'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    /** @deprecated Kept so existing v9.8.3 lab accounts remain usable. */
    public const ROLE_LAB = 'lab';

    public const ROLE_LAB_STAFF = 'lab_staff';

    public const ROLE_LAB_ADMIN = 'lab_admin';

    public const ROLE_HOSPITAL = 'hospital';

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    /**
     * Legacy accounts created before the role migration are treated as the
     * bootstrap administrator until the migration assigns explicit roles.
     */
    public function isSystemAdministrator(): bool
    {
        $role = $this->normalizedAccessValue('role');

        return $role === '' || $role === self::ROLE_ADMIN;
    }

    /**
     * Account management is an Administrator-only capability.
     */
    public function canManageUsers(): bool
    {
        return $this->isSystemAdministrator();
    }

    /**
     * Keep Backpack avatar rendering completely local. The default Gravatar
     * helper downloads and caches one remote image per email address, which
     * can leave a newly approved account on a partially rendered admin shell
     * while that external request times out.
     */
    public function bloodCareAvatarUrl(): string
    {
        return asset('images/bloodcare-staff-avatar.svg');
    }

    public function canAccessStaffWorkspace(): bool
    {
        if ((bool) $this->getAttribute('is_banned')) {
            return false;
        }

        $role = $this->normalizedAccessValue('role');
        $approvalStatus = $this->normalizedAccessValue('approval_status');

        // Preserve the original bootstrap administrator created before role
        // fields existed, but require every explicit Staff/Admin account to be
        // approved before it can enter the workspace.
        if ($role === '') {
            return true;
        }

        if (! in_array($role, [
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_LAB,
            self::ROLE_LAB_STAFF,
            self::ROLE_LAB_ADMIN,
        ], true)) {
            return false;
        }

        return $approvalStatus === self::APPROVAL_APPROVED
            || ($role === self::ROLE_ADMIN && $approvalStatus === '');
    }

    public function donor(): HasOne
    {
        return $this->hasOne(Donor::class);
    }

    public function hospital(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function isHospitalUser(): bool
    {
        return $this->normalizedAccessValue('role') === self::ROLE_HOSPITAL
            && ! (bool) $this->is_banned
            && $this->normalizedAccessValue('approval_status') === self::APPROVAL_APPROVED
            && $this->hospital_id !== null
            && (bool) $this->hospital?->is_active;
    }

    public function canUseLaboratory(): bool
    {
        return $this->isLaboratoryUser();
    }

    public function canManageBloodBank(): bool
    {
        return in_array($this->normalizedAccessValue('role'), [self::ROLE_ADMIN, self::ROLE_STAFF], true);
    }

    /**
     * Laboratory staff use a separate workspace. The legacy `lab` role is
     * treated as a laboratory administrator so v9.8.3 accounts do not break.
     */
    public function isLaboratoryUser(): bool
    {
        return in_array($this->normalizedAccessValue('role'), [
            self::ROLE_LAB,
            self::ROLE_LAB_STAFF,
            self::ROLE_LAB_ADMIN,
        ], true);
    }

    public function isLaboratoryAdministrator(): bool
    {
        return in_array($this->normalizedAccessValue('role'), [self::ROLE_LAB, self::ROLE_LAB_ADMIN], true);
    }

    /**
     * Inventory is deliberately shared by the system and laboratory branches.
     * Laboratory tests/components remain laboratory-only.
     */
    public function canUseBloodInventory(): bool
    {
        return $this->canManageBloodBank() || $this->isLaboratoryUser();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Stable UI categories for the System Administrator's Users page.
     * Hospital portal identities are managed separately under Hospital Services.
     */
    public function managementRoleLabel(): string
    {
        return match ($this->normalizedAccessValue('role')) {
            self::ROLE_ADMIN => 'System Admin',
            self::ROLE_STAFF => 'System Staff',
            self::ROLE_LAB_STAFF => 'Lab Staff',
            self::ROLE_LAB, self::ROLE_LAB_ADMIN => 'Lab Admin',
            default => 'User',
        };
    }

    public function verifiedScreenings(): HasMany
    {
        return $this->hasMany(DonorScreening::class, 'verified_by_staff_id');
    }

    private function normalizedAccessValue(string $attribute): string
    {
        return strtolower(trim((string) $this->getAttribute($attribute)));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'approved_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_used_step' => 'integer',
        ];
    }
}
