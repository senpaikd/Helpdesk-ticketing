@props(['ticket', 'staff'])

@php
    $closeUrl = route('tickets.index', request()->except(['ticket', 'page']));
    $mutationQuery = http_build_query(array_merge(request()->except(['ticket', 'page']), ['ticket' => $ticket->ticket_number]));
    $mutationSuffix = $mutationQuery === '' ? '' : '?'.$mutationQuery;
@endphp

<div class="workspace-drawer-mask" data-workspace-drawer-close-url="{{ $closeUrl }}"></div>
<aside class="workspace-ticket-drawer" data-workspace-ticket-drawer aria-labelledby="workspace-drawer-title">
    <header class="workspace-drawer-header">
        <div>
            <span class="workspace-ticket-id">{{ $ticket->ticket_number }} · {{ $ticket->creator->name }}</span>
            <h2 id="workspace-drawer-title" tabindex="-1">{{ $ticket->subject }}</h2>
        </div>
        <a href="{{ $closeUrl }}" class="workspace-icon-button" aria-label="Close ticket drawer"><i class="ph ph-x" aria-hidden="true"></i></a>
    </header>

    <div class="workspace-drawer-controls">
        <div><span>Status</span><strong>{{ $ticket->status->label() }}</strong></div>
        <div><span>Priority</span><strong>{{ $ticket->priority->label() }}</strong></div>
        <div><span>Assignee</span><strong>{{ $ticket->assignee?->name ?? 'Unassigned' }}</strong></div>
    </div>

    <div class="workspace-drawer-scroll">
        <section class="workspace-ticket-description">
            <p class="workspace-eyebrow">{{ $ticket->category->name }} · Created {{ $ticket->created_at->diffForHumans() }}</p>
            <p>{{ $ticket->description }}</p>
        </section>

        <section class="workspace-drawer-action">
            <h3>Ticket controls</h3>
            <div class="workspace-control-grid">
                @can('updateStatus', $ticket)
                    <form method="POST" action="{{ route('tickets.status.update', $ticket).$mutationSuffix }}">
                        @csrf @method('PATCH')
                        <select name="status" class="portal-field" aria-label="Update ticket status">
                            @foreach(\App\Enums\TicketStatus::cases() as $status)
                                @if($ticket->status->canTransitionTo($status, auth()->user()->role))<option value="{{ $status->value }}">{{ $status->label() }}</option>@endif
                            @endforeach
                        </select>
                        <button class="workspace-button workspace-button-secondary" type="submit">Update status</button>
                    </form>
                @endcan
                @can('updatePriority', $ticket)
                    <form method="POST" action="{{ route('tickets.priority.update', $ticket).$mutationSuffix }}">
                        @csrf @method('PATCH')
                        <select name="priority" class="portal-field" aria-label="Update ticket priority">
                            @foreach(\App\Enums\TicketPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>@endforeach
                        </select>
                        <button class="workspace-button workspace-button-secondary" type="submit">Update priority</button>
                    </form>
                @endcan
            </div>
        </section>

        @can('assign', $ticket)
            <section class="workspace-drawer-action">
                <h3>Assignment</h3>
                <form method="POST" action="{{ route('tickets.assignment.update', $ticket).$mutationSuffix }}">
                    @csrf @method('PATCH')
                    @if(auth()->user()->role === \App\Enums\UserRole::Admin)
                        <select name="assigned_to" class="portal-field">
                            <option value="">Unassigned</option>
                            @foreach($staff as $member)<option value="{{ $member->id }}" @selected($ticket->assigned_to === $member->id)>{{ $member->name }}</option>@endforeach
                        </select>
                    @else
                        <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                    @endif
                    <button class="workspace-button workspace-button-primary" type="submit">{{ auth()->user()->role === \App\Enums\UserRole::Admin ? 'Update assignment' : 'Claim ticket' }}</button>
                </form>
            </section>
        @endcan

        <section class="workspace-conversation">
            <h3>Conversation</h3>
            @forelse($ticket->comments->sortBy('created_at') as $comment)
                <article class="workspace-message {{ $comment->user_id === auth()->id() ? 'workspace-message-own' : '' }}">
                    <span class="workspace-avatar">{{ str($comment->user->name)->substr(0, 1)->upper() }}</span>
                    <div><strong>{{ $comment->user->name }}</strong><p>{{ $comment->message }}</p><small>{{ $comment->created_at->diffForHumans() }}</small></div>
                </article>
            @empty
                <p class="workspace-table-muted">No replies yet.</p>
            @endforelse
        </section>

        <section class="workspace-drawer-action">
            <h3>Attachments</h3>
            @forelse($ticket->attachments as $attachment)
                <a class="workspace-attachment-link" href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"><i class="ph ph-paperclip" aria-hidden="true"></i>{{ $attachment->original_name }}</a>
            @empty
                <p class="workspace-table-muted">No attachments.</p>
            @endforelse
            @can('attach', $ticket)
                <form method="POST" action="{{ route('tickets.attachments.store', $ticket).$mutationSuffix }}" enctype="multipart/form-data" class="workspace-upload-form">
                    @csrf
                    <input type="file" name="attachment" class="portal-field" aria-label="Choose attachment">
                    <button type="submit" class="workspace-button workspace-button-secondary">Upload</button>
                </form>
            @endcan
        </section>

        <section class="workspace-drawer-action">
            <h3>Activity</h3>
            @foreach($ticket->activities->sortByDesc('created_at') as $activity)
                <p class="workspace-activity-line"><strong>{{ $activity->user?->name ?? 'System' }}</strong> {{ strtolower($activity->action->label()) }} <small>{{ $activity->created_at->diffForHumans() }}</small></p>
            @endforeach
        </section>
    </div>

    @can('comment', $ticket)
        <footer class="workspace-drawer-reply">
            <form method="POST" action="{{ route('tickets.comments.store', $ticket).$mutationSuffix }}">
                @csrf
                <label for="drawer-message">Reply to requester</label>
                <textarea id="drawer-message" name="message" rows="3" class="portal-field" placeholder="Write a reply…">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="workspace-button workspace-button-primary">Send reply</button>
            </form>
        </footer>
    @endcan
</aside>
