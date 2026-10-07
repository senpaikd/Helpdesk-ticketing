<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuthorizedTicketQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketController extends Controller
{
    public function __construct(private readonly AuthorizedTicketQuery $authorizedTicketQuery) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === UserRole::User) {
            return redirect(route('portal', absolute: false).'#my-tickets');
        }

        $this->authorize('viewAny', Ticket::class);

        $query = $this->authorizedTicketQuery->for($request->user())
            ->with(['creator', 'assignee', 'category'])
            ->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn (Builder $creator): Builder => $creator->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = TicketStatus::tryFrom((string) $request->query('status'))) {
            $query->where('status', $status);
        }

        if ($priority = TicketPriority::tryFrom((string) $request->query('priority'))) {
            $query->where('priority', $priority);
        }

        $categoryId = filter_var($request->query('category'), FILTER_VALIDATE_INT);
        if ($categoryId !== false) {
            $query->where('category_id', $categoryId);
        }

        $assigneeId = filter_var($request->query('assignee'), FILTER_VALIDATE_INT);
        if ($request->user()->role === UserRole::Admin && $assigneeId !== false) {
            $query->where('assigned_to', $assigneeId);
        }

        $selectedTicket = null;
        $selectedNumber = trim((string) $request->query('ticket', ''));
        if ($selectedNumber !== '') {
            $selectedTicket = Ticket::query()->where('ticket_number', $selectedNumber)->firstOrFail();
            $this->authorize('view', $selectedTicket);
            $selectedTicket->load(['creator', 'assignee', 'category', 'comments.user', 'attachments.uploader', 'activities.user']);
        }

        return view('tickets.index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'staff' => User::query()->where('role', UserRole::Staff)->where('is_active', true)->orderBy('name')->get(),
            'selectedTicket' => $selectedTicket,
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'priority' => $priority?->value,
                'category' => $categoryId === false ? null : $categoryId,
                'assignee' => $request->user()->role === UserRole::Admin && $assigneeId !== false ? $assigneeId : null,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTicketRequest $request, CreateTicketAction $action): RedirectResponse
    {
        $ticket = $action->execute($request->user(), $request->validated(), $request->file('attachment'));

        return redirect()->route('tickets.show', $ticket)->with('success', 'Your ticket has been submitted.');
    }

    public function show(Request $request, Ticket $ticket): View|RedirectResponse
    {
        $this->authorize('view', $ticket);

        if ($request->user()->role !== UserRole::User) {
            return redirect()->route('tickets.index', ['ticket' => $ticket->ticket_number]);
        }

        $ticket->load(['creator', 'assignee', 'category', 'comments.user', 'attachments.uploader', 'activities.user']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'staff' => User::query()->where('role', UserRole::Staff)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
