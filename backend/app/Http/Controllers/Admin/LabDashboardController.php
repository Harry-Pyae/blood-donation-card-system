<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BloodUnit;
use App\Models\Donation;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabDashboardController extends Controller
{
    private const HISTORY_SOURCES = ['admin-laboratory', 'admin-components', 'admin-inventory'];

    public function index(): View
    {
        $today = now()->toDateString();
        $pendingTests = Donation::query()
            ->where('status', 'accepted')
            ->whereHas('bloodUnit', fn ($query) => $query->where('status', 'quarantined'))
            ->whereDoesntHave('labTest')
            ->count();
        $releasedToday = LabTest::query()
            ->where('release_status', 'released')
            ->whereDate('tested_at', $today)
            ->count();
        $componentStock = BloodUnit::query()
            ->whereNotNull('parent_blood_unit_id')
            ->where('status', 'available')
            ->whereDate('expires_at', '>=', $today)
            ->count();
        $expiringSoon = BloodUnit::query()
            ->where('status', 'available')
            ->whereDate('expires_at', '>=', $today)
            ->whereDate('expires_at', '<=', now()->addDays(7)->toDateString())
            ->count();

        $recentTests = LabTest::query()
            ->with(['donation', 'testedBy'])
            ->latest('tested_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('lab.dashboard', compact(
            'pendingTests',
            'releasedToday',
            'componentStock',
            'expiringSoon',
            'recentTests',
        ));
    }

    public function history(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = strtolower(trim((string) $request->query('type', 'all')));
        $sourceByType = [
            'laboratory' => 'admin-laboratory',
            'components' => 'admin-components',
            'inventory' => 'admin-inventory',
        ];

        if ($type !== 'all' && ! array_key_exists($type, $sourceByType)) {
            $type = 'all';
        }

        $logs = ActivityLog::query()
            ->with(['user', 'subject'])
            ->whereIn('source', self::HISTORY_SOURCES)
            ->when($type !== 'all', fn ($query) => $query->where('source', $sourceByType[$type]))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('action', 'like', '%'.$search.'%')
                        ->orWhere('details', 'like', '%'.$search.'%')
                        ->orWhere('result', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('lab.history', compact('logs', 'search', 'type'));
    }
}
