<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('bloodcare:staff-check {email}', function (string $email) {
    $guard = (string) config('backpack.base.guard', 'web');
    $user = App\Models\User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();

    $this->line('Backpack guard: '.$guard);
    $this->line('Guard defined: '.(config("auth.guards.{$guard}") ? 'yes' : 'no'));

    if (! $user) {
        $this->error('No user was found for that email.');
        return 1;
    }

    $this->table(['Field', 'Value'], [
        ['ID', (string) $user->id],
        ['Role', (string) $user->role],
        ['Approval', (string) $user->approval_status],
        ['Banned', $user->is_banned ? 'yes' : 'no'],
        ['Workspace access', $user->canAccessStaffWorkspace() ? 'yes' : 'no'],
    ]);

    return $user->canAccessStaffWorkspace() ? 0 : 2;
})->purpose('Check an approved staff account and the active Backpack guard');
