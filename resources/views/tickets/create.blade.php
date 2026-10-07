@extends(auth()->user()->role === \App\Enums\UserRole::User ? 'layouts.portal' : 'layouts.workspace')
@section('title', 'Create ticket — HelpDesk')
@section('content')
<div class="mx-auto max-w-3xl">
    <a class="text-sm font-semibold text-[var(--portal-primary)]" href="{{ route('tickets.index') }}">← Back to tickets</a>
    <div class="mt-5 rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm sm:p-9">
        <p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--portal-primary)]">New request</p><h1 class="mt-2 text-3xl font-bold tracking-tight">How can we help?</h1><p class="mt-2 text-[var(--portal-muted)]">Share enough detail for the support team to get started.</p>
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="mt-8 grid gap-6">@csrf
            <label class="grid gap-2 text-sm font-semibold">Category<select class="portal-field" name="category_id" required><option value="">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <label class="grid gap-2 text-sm font-semibold">Subject<input class="portal-field" name="subject" value="{{ old('subject') }}" maxlength="255" required placeholder="A short summary of the issue">@error('subject')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <label class="grid gap-2 text-sm font-semibold">Description<textarea class="portal-field min-h-40 resize-y" name="description" required placeholder="What happened, and what have you already tried?">{{ old('description') }}</textarea>@error('description')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <label class="grid gap-2 text-sm font-semibold">Priority<select class="portal-field" name="priority" required>@foreach(\App\Enums\TicketPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected(old('priority', 'medium') === $priority->value)>{{ $priority->label() }}</option>@endforeach</select>@error('priority')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <label class="grid gap-2 text-sm font-semibold">Attachment <span class="font-normal text-[var(--portal-muted)]">Optional · JPG, PNG, PDF, TXT, DOC, or DOCX · max 5 MB</span><input class="portal-field" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.txt,.doc,.docx">@error('attachment')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <div class="flex justify-end gap-3"><a href="{{ route('tickets.index') }}" class="portal-button portal-button-secondary">Cancel</a><button class="portal-button portal-button-primary" type="submit">Submit ticket</button></div>
        </form>
    </div>
</div>
@endsection
