<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdverseReaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationCentreController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->staffUser($request);
        $filter = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'unread'])],
        ])['filter'] ?? 'all';

        $query = $filter === 'unread' ? $user->unreadNotifications() : $user->notifications();
        $notifications = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'totalCount' => $user->notifications()->count(),
            'routePrefix' => $user->isLaboratoryUser() ? 'bloodcare.lab.notifications' : 'bloodcare.admin.notifications',
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $user = $this->staffUser($request);
        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return back()->with('status', __('bloodcare.notifications.read_saved'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $this->staffUser($request);

        return response()->json(['count' => $user->unreadNotifications()->count()]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $user = $this->staffUser($request);
        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $data = is_array($item->data) ? $item->data : [];
        [$targetRoute, $routeParameters, $query, $fragment] = $this->destination($user, $data);
        if (is_string($targetRoute) && Route::has($targetRoute)) {
            try {
                $url = route($targetRoute, $routeParameters);
                if ($query !== []) {
                    $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query, '', '&', PHP_QUERY_RFC3986);
                }
                if ($fragment !== '') {
                    $url .= '#'.rawurlencode($fragment);
                }

                return redirect()->to($url);
            } catch (\Throwable) {
                // Old or malformed notification metadata should never strand
                // a user on an error page. Fall back to their own inbox.
            }
        }

        return redirect()->route(
            $user->isLaboratoryUser() ? 'bloodcare.lab.notifications' : 'bloodcare.admin.notifications'
        );
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $this->staffUser($request);
        $user->unreadNotifications->markAsRead();

        return back()->with('status', __('bloodcare.notifications.all_read_saved'));
    }

    /**
     * New notifications carry exact route metadata. This compatibility layer
     * also upgrades notifications already stored by v10.1.0 so users do not
     * need to reseed or recreate them just to get deep links.
     *
     * @return array{0:?string,1:array<string,mixed>,2:array<string,mixed>,3:string}
     */
    private function destination(User $user, array $data): array
    {
        $targetRoute = is_string($data['route_name'] ?? null) ? $data['route_name'] : null;
        $routeParameters = is_array($data['route_parameters'] ?? null) ? $data['route_parameters'] : [];
        $query = is_array($data['route_query'] ?? null) ? $data['route_query'] : [];
        $fragment = is_string($data['route_fragment'] ?? null) ? trim($data['route_fragment'], '# ') : '';
        $parameters = is_array($data['parameters'] ?? null) ? $data['parameters'] : [];
        $event = (string) ($data['event'] ?? '');

        if ($event === 'reaction_reported') {
            if ($user->isLaboratoryUser()) {
                $targetRoute = 'bloodcare.lab.inventory';
                $query = ['unit' => $parameters['unit'] ?? null];
            } else {
                $reaction = isset($parameters['reference'])
                    ? AdverseReaction::query()->where('reference', $parameters['reference'])->first()
                    : null;
                if ($reaction) {
                    $targetRoute = 'bloodcare.admin.haemovigilance.show';
                    $routeParameters = ['reaction' => $reaction->getRouteKey()];
                    $query = [];
                }
            }
        } elseif ($event === 'unit_quarantined' && $user->isLaboratoryUser()) {
            $query = ['q' => $parameters['unit'] ?? null];
        } elseif (in_array($event, ['unit_released', 'unit_discarded'], true) && ! $user->isLaboratoryUser()) {
            $query = ['unit' => $parameters['unit'] ?? null];
        } elseif ($event === 'hospital_request' && ! $user->isLaboratoryUser()) {
            $query = ['q' => $parameters['reference'] ?? null];
        } elseif ($event === 'staff_registration' && ! $user->isLaboratoryUser()) {
            $query = ['q' => $parameters['email'] ?? null];
        }

        return [
            $targetRoute,
            $routeParameters,
            array_filter($query, fn ($value) => $value !== null && $value !== ''),
            $fragment,
        ];
    }

    private function staffUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canAccessStaffWorkspace() && ! $user->isHospitalUser(), 403);

        return $user;
    }
}
