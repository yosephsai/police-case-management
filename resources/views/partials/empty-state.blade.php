<div class="empty-state">
    @include('partials.icon', ['name' => $icon ?? 'inbox'])
    <strong>{{ $title ?? 'Nothing to show' }}</strong>
    @if(($message ?? null))
        <span>{{ $message }}</span>
    @endif
</div>
