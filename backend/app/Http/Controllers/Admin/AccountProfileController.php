<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function edit(): View
    {
        /** @var User $user */
        $user = backpack_user();

        return view('vendor.backpack.theme-tabler.my_account', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = backpack_user();
        abort_unless($user, 403);

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
            'job_title' => trim((string) $request->input('job_title')),
            'workplace' => trim((string) $request->input('workplace')),
            'registration_note' => trim((string) $request->input('registration_note')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'workplace' => ['nullable', 'string', 'max:150'],
            'registration_note' => ['nullable', 'string', 'max:500'],
            'current_password' => ['nullable', 'string'],
        ]);

        if ($data['email'] !== Str::lower((string) $user->email)) {
            if (! filled($data['current_password'] ?? null) || ! Hash::check((string) $data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => __('bloodcare.profile.current_password_invalid'),
                ]);
            }
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
            'job_title' => filled($data['job_title'] ?? null) ? $data['job_title'] : null,
            'workplace' => filled($data['workplace'] ?? null) ? $data['workplace'] : null,
            'registration_note' => filled($data['registration_note'] ?? null) ? $data['registration_note'] : null,
        ]);

        ActivityLog::record([
            'type' => 'User',
            'action' => 'Own profile updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => $user->id,
            'result' => 'Updated',
            'details' => $user->email,
            'source' => 'account-profile',
        ]);

        return redirect()->route('backpack.account.info')->with('success', __('bloodcare.profile.saved'));
    }

    public function changePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = backpack_user();
        abort_unless($user, 403);

        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        if (! Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => __('bloodcare.profile.old_password_invalid'),
            ]);
        }

        $user->update(['password' => $data['new_password']]);

        ActivityLog::record([
            'type' => 'User',
            'action' => 'Own password changed',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => $user->id,
            'result' => 'Updated',
            'details' => $user->email,
            'source' => 'account-profile',
        ]);

        return redirect()->route('backpack.account.info')->with('success', __('bloodcare.profile.password_saved'));
    }
}
