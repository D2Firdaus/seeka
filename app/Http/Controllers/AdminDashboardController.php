<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Dispute;
use App\Models\Handover;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Tampilkan ringkasan statistik, hal yang menunggu tindakan, dan tren laporan.
     */
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'lost' => Item::where('type', 'lost')->count(),
                'found' => Item::where('type', 'found')->count(),
                'claims_pending' => Claim::where('status', 'pending')->count(),
                'disputes_open' => Dispute::where('status', 'open')->count(),
                'handovers' => Handover::count(),
            ],
            'pendingClaims' => Claim::query()
                ->with(['foundItem', 'claimant'])
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
            'openDisputes' => Dispute::query()
                ->with(['claim.foundItem', 'reportedBy'])
                ->where('status', 'open')
                ->latest()
                ->limit(5)
                ->get(),
            'activities' => $this->activities(),
            'trend' => $this->monthlyTrend(),
            'statusCounts' => Item::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($total) => (int) $total),
        ]);
    }

    /**
     * Gabungkan entitas terbaru dari laporan, klaim, banding, dan serah terima.
     *
     * @return Collection<int, array{type: string, title: string, subtitle: string, at: Carbon}>
     */
    private function activities(): Collection
    {
        $items = Item::with('user')->latest()->limit(5)->get()
            ->map(fn (Item $item) => [
                'type' => $item->type,
                'title' => $item->title,
                'subtitle' => 'oleh '.($item->user?->name ?? 'Pengguna'),
                'at' => $item->created_at,
            ]);

        $claims = Claim::with(['foundItem', 'claimant'])->latest()->limit(5)->get()
            ->map(fn (Claim $claim) => [
                'type' => 'claim',
                'title' => $claim->foundItem?->title ?? 'Barang temuan',
                'subtitle' => 'Klaim oleh '.($claim->claimant?->name ?? 'Pengguna'),
                'at' => $claim->created_at,
            ]);

        $disputes = Dispute::with(['claim.foundItem', 'reportedBy'])->latest()->limit(5)->get()
            ->map(fn (Dispute $dispute) => [
                'type' => 'dispute',
                'title' => $dispute->claim?->foundItem?->title ?? 'Barang temuan',
                'subtitle' => 'Banding oleh '.($dispute->reportedBy?->name ?? 'Pengguna'),
                'at' => $dispute->created_at,
            ]);

        $handovers = Handover::with(['claim.foundItem', 'handedBy', 'receivedBy'])->latest()->limit(5)->get()
            ->map(fn (Handover $handover) => [
                'type' => 'handover',
                'title' => $handover->claim?->foundItem?->title ?? 'Barang temuan',
                'subtitle' => 'Serah terima dari '.($handover->handedBy?->name ?? 'Pengguna').' ke '.($handover->receivedBy?->name ?? 'Pengguna'),
                'at' => $handover->created_at,
            ]);

        return $items->concat($claims)->concat($disputes)->concat($handovers)
            ->sortByDesc(fn (array $activity) => $activity['at']->timestamp)
            ->take(8)
            ->values();
    }

    /**
     * Hitung jumlah laporan per bulan selama enam bulan terakhir.
     *
     * @return list<array{label: string, count: int}>
     */
    private function monthlyTrend(): array
    {
        $perMonth = Item::query()
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['created_at'])
            ->countBy(fn (Item $item) => $item->created_at->format('Y-m'));

        return collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth())
            ->map(fn (Carbon $month) => [
                'label' => $month->locale('id')->translatedFormat('M'),
                'count' => (int) ($perMonth[$month->format('Y-m')] ?? 0),
            ])
            ->all();
    }
}
