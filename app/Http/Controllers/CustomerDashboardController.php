<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::User) {
            return redirect()->route('dashboard');
        }

        $ownedTickets = $user->createdTickets();

        $stats = [
            'total' => (clone $ownedTickets)->count(),
            'open' => (clone $ownedTickets)->where('status', TicketStatus::Open)->count(),
            'in_progress' => (clone $ownedTickets)->where('status', TicketStatus::InProgress)->count(),
            'resolved' => (clone $ownedTickets)->where('status', TicketStatus::Resolved)->count(),
        ];

        $recentTickets = $user->createdTickets()
            ->with(['category', 'assignee'])
            ->latest()
            ->limit(5)
            ->get();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => TicketStatus::tryFrom((string) $request->query('status')),
            'priority' => TicketPriority::tryFrom((string) $request->query('priority')),
            'category' => filter_var($request->query('category'), FILTER_VALIDATE_INT),
        ];

        $tickets = $user->createdTickets()
            ->with(['category', 'assignee'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->where('ticket_number', 'like', "%{$filters['search']}%")
                        ->orWhere('subject', 'like', "%{$filters['search']}%");
                });
            })
            ->when($filters['status'], fn (Builder $query, TicketStatus $status) => $query->where('status', $status))
            ->when($filters['priority'], fn (Builder $query, TicketPriority $priority) => $query->where('priority', $priority))
            ->when($filters['category'] !== false, fn (Builder $query) => $query->where('category_id', $filters['category']))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('customer.dashboard', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'tickets' => $tickets,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
