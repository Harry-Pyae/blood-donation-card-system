<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffRegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        abort_unless(config('bloodcare.staff_registration_open'), 404);

        if (backpack_auth()->check()) {
            return redirect(backpack_url('dashboard'));
        }

        return view('auth.staff-register');
    }

    public function store(Request $request, BloodCareNotificationService $notifications): RedirectResponse
    {
        abort_unless(config('bloodcare.staff_registration_open'), 404);

        if (backpack_auth()->check()) {
            return redirect(backpack_url('dashboard'));
        }

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
            'job_title' => trim((string) $request->input('job_title')),
            'workplace' => trim((string) $request->input('workplace')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'job_title' => ['required', 'string', 'min:2', 'max:100'],
            'workplace' => ['required', 'string', 'min:2', 'max:150'],
            'registration_note' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => __('bloodcare.staff_register.terms_required'),
        ]);

        $user = DB::transaction(function () use ($data, $notifications): User {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'phone' => trim($data['phone']),
                'job_title' => trim($data['job_title']),
                'workplace' => trim($data['workplace']),
                'registration_note' => filled($data['registration_note'] ?? null)
                    ? trim((string) $data['registration_note'])
                    : null,
                'password' => $data['password'],
                'role' => User::ROLE_STAFF,
                'approval_status' => User::APPROVAL_PENDING,
                'approved_at' => null,
                'approved_by' => null,
                'is_banned' => false,
            ]);

            ActivityLog::record([
                'type' => 'User',
                'action' => 'Staff registration submitted',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'result' => 'Pending',
                'details' => "{$user->email}; {$user->job_title}; {$user->workplace}",
                'source' => 'staff-registration',
                'metadata' => [
                    'phone' => $user->phone,
                    'job_title' => $user->job_title,
                    'workplace' => $user->workplace,
                ],
            ]);

            $notifications->staffRegistrationSubmitted($user);

            return $user;
        });

        return redirect()
            ->route('backpack.auth.login')
            ->with('status', __('bloodcare.staff_register.submitted'));
    }
}
