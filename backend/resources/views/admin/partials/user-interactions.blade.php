@php
    $userConfig = [
        ...$userData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'roleUrlTemplate' => route('bloodcare.admin.users.role', ['user' => '__REFERENCE__']),
        'statusUrlTemplate' => route('bloodcare.admin.users.status', ['user' => '__REFERENCE__']),
        'approvalUrlTemplate' => route('bloodcare.admin.users.approval', ['user' => '__REFERENCE__']),
        'labels' => [
            'allRoles' => __('bloodcare.users.all_roles'),
            'allStatuses' => __('bloodcare.users.all_statuses'),
            'userRole' => __('bloodcare.users.user_role'),
            'systemStaffRole' => __('bloodcare.users.system_staff_role'),
            'systemAdminRole' => __('bloodcare.users.system_admin_role'),
            'labStaffRole' => __('bloodcare.users.lab_staff_role'),
            'labAdminRole' => __('bloodcare.users.lab_admin_role'),
            'active' => __('bloodcare.users.active'),
            'pending' => __('bloodcare.users.pending'),
            'rejected' => __('bloodcare.users.rejected'),
            'banned' => __('bloodcare.users.banned'),
            'showingRange' => __('bloodcare.users.showing_range'),
            'noResults' => __('bloodcare.users.no_results'),
            'openActions' => __('bloodcare.users.open_actions'),
            'currentSession' => __('bloodcare.users.current_session'),
            'never' => __('bloodcare.users.never'),
            'view' => __('bloodcare.users.view'),
            'manageRole' => __('bloodcare.users.manage_role'),
            'ban' => __('bloodcare.users.ban'),
            'restore' => __('bloodcare.users.restore'),
            'approve' => __('bloodcare.users.approve'),
            'reject' => __('bloodcare.users.reject'),
            'accountId' => __('bloodcare.users.account_id'),
            'fullName' => __('bloodcare.users.full_name'),
            'email' => __('bloodcare.users.email'),
            'role' => __('bloodcare.users.role'),
            'status' => __('bloodcare.users.status'),
            'lastLogin' => __('bloodcare.users.last_login'),
            'joined' => __('bloodcare.users.joined'),
            'phone' => __('bloodcare.users.phone'),
            'jobTitle' => __('bloodcare.users.job_title'),
            'workplace' => __('bloodcare.users.workplace'),
            'registrationNote' => __('bloodcare.users.registration_note'),
            'approvedAt' => __('bloodcare.users.approved_at'),
            'roleTitle' => __('bloodcare.users.role_title'),
            'roleHelp' => __('bloodcare.users.role_help'),
            'banTitle' => __('bloodcare.users.ban_title'),
            'restoreTitle' => __('bloodcare.users.restore_title'),
            'approveTitle' => __('bloodcare.users.approve_title'),
            'approveRoleHelp' => __('bloodcare.users.approve_role_help'),
            'rejectTitle' => __('bloodcare.users.reject_title'),
            'resetTitle' => __('bloodcare.users.reset_title'),
            'confirmBan' => __('bloodcare.users.confirm_ban'),
            'confirmRestore' => __('bloodcare.users.confirm_restore'),
            'confirmApprove' => __('bloodcare.users.confirm_approve'),
            'confirmReject' => __('bloodcare.users.confirm_reject'),
            'confirmReset' => __('bloodcare.users.confirm_reset'),
            'confirmAction' => __('bloodcare.users.confirm_action'),
            'saveRole' => __('bloodcare.users.save_role'),
            'roleUpdated' => __('bloodcare.users.role_updated'),
            'bannedMessage' => __('bloodcare.users.banned_message'),
            'restoredMessage' => __('bloodcare.users.restored_message'),
            'approvedMessage' => __('bloodcare.users.approved_message'),
            'approvedRoleMessage' => __('bloodcare.users.approved_role_message'),
            'rejectedMessage' => __('bloodcare.users.rejected_message'),
            'resetMessage' => __('bloodcare.users.reset_message'),
            'protectCurrent' => __('bloodcare.users.protect_current'),
            'protectLastAdmin' => __('bloodcare.users.protect_last_admin'),
            'resetSample' => __('bloodcare.users.reset_sample'),
        ],
    ];
@endphp

<script id="bc-user-config" type="application/json">@json($userConfig)</script>

<div class="bc-modal" id="bc-user-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.users.close') }}"></button>
    <section class="bc-modal-dialog bc-user-details-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-user-details-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.users.details_eyebrow') }}</p>
                <h2 id="bc-user-details-title">{{ __('bloodcare.users.details_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.users.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-user-details-content" id="bc-user-details-content"></div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.users.close') }}</button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-user-role-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.users.cancel') }}"></button>
    <section class="bc-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-user-role-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.users.role_eyebrow') }}</p>
                <h2 id="bc-user-role-title">{{ __('bloodcare.users.role_title') }}</h2>
                <p id="bc-user-role-help">{{ __('bloodcare.users.role_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.users.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <form id="bc-user-role-form">
            <p class="bc-user-role-account" id="bc-user-role-account"></p>
            <div class="bc-role-choice-grid">
                @foreach ([
                    ['value' => 'User', 'icon' => 'la-user', 'title' => __('bloodcare.users.user_role'), 'help' => __('bloodcare.users.user_help')],
                    ['value' => 'System Staff', 'icon' => 'la-user-nurse', 'title' => __('bloodcare.users.system_staff_role'), 'help' => __('bloodcare.users.system_staff_help')],
                    ['value' => 'System Admin', 'icon' => 'la-user-shield', 'title' => __('bloodcare.users.system_admin_role'), 'help' => __('bloodcare.users.system_admin_help')],
                    ['value' => 'Lab Staff', 'icon' => 'la-vials', 'title' => __('bloodcare.users.lab_staff_role'), 'help' => __('bloodcare.users.lab_staff_help')],
                    ['value' => 'Lab Admin', 'icon' => 'la-flask', 'title' => __('bloodcare.users.lab_admin_role'), 'help' => __('bloodcare.users.lab_admin_help')],
                ] as $role)
                    <label class="bc-role-choice" data-role-choice="{{ $role['value'] }}">
                        <input type="radio" name="role" value="{{ $role['value'] }}" required>
                        <span class="bc-role-choice-card">
                            <i class="la {{ $role['icon'] }}"></i>
                            <span><strong>{{ $role['title'] }}</strong><small>{{ $role['help'] }}</small></span>
                            <i class="la la-check-circle bc-role-choice-check"></i>
                        </span>
                    </label>
                @endforeach
            </div>
            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.users.cancel') }}</button>
                <button class="btn bc-btn-primary" id="bc-user-role-submit" type="submit">
                    <i class="la la-save"></i> <span id="bc-user-role-submit-label">{{ __('bloodcare.users.save_role') }}</span>
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-user-confirm" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.users.cancel') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small bc-confirm-dialog" role="alertdialog" aria-modal="true"
             aria-labelledby="bc-user-confirm-title" aria-describedby="bc-user-confirm-message">
        <header class="bc-confirm-header">
            <span class="bc-confirm-icon" id="bc-user-confirm-icon" aria-hidden="true"><i class="la la-user-lock"></i></span>
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.users.confirm_eyebrow') }}</p>
                <h2 id="bc-user-confirm-title">{{ __('bloodcare.users.confirm_action') }}</h2>
            </div>
        </header>
        <p class="bc-confirm-message" id="bc-user-confirm-message"></p>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.users.cancel') }}</button>
            <button class="btn bc-btn-primary" id="bc-user-confirm-submit" type="button">{{ __('bloodcare.users.confirm_action') }}</button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-user-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.users.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-user-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.users.data_source_eyebrow') }}</p>
                <h2 id="bc-user-data-source-title">{{ __('bloodcare.users.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.users.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-user-shield"></i></span>
            <div>
                <strong>{{ __('bloodcare.users.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.users.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.users.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-users" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.users.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-user-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
