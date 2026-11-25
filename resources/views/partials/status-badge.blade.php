@php
    $variants = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'open' => 'info',
        'under_investigation' => 'info',
        'pending_review' => 'warning',
        'closed' => 'success',
        'accepted' => 'success',
        'returned' => 'danger',
        'active' => 'success',
        'inactive' => 'danger',
    ];

    $badgeVariant = $variant ?? ($variants[$status] ?? 'neutral');
    $badgeLabel = $label ?? ucwords(str_replace('_', ' ', (string) $status));
@endphp

<span class="badge badge--{{ $badgeVariant }}">{{ $badgeLabel }}</span>
