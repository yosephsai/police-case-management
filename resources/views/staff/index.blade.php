@extends('layouts.app')

@section('title', 'User Management')
@section('heading', 'User Management')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>User Management</h1>
        <p class="page-subtitle">Manage staff accounts and role assignments.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.create') }}" class="btn btn--primary">
            @include('partials.icon', ['name' => 'plus'])
            Add New Staff
        </a>
    </div>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Registered</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr>
                        <td>{{ $member->id }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>@include('partials.status-badge', ['status' => 'neutral', 'label' => $member->roleLabel()])</td>
                        <td>@include('partials.status-badge', ['status' => $member->is_active ? 'active' : 'inactive'])</td>
                        <td class="nowrap">{{ $member->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="row-actions justify-end">
                                <a href="{{ route('staff.edit', $member) }}" class="btn btn--outline btn--sm">Edit</a>

                                @can('manage-permissions')
                                    <a href="{{ route('staff.permissions.edit', $member) }}" class="btn btn--outline btn--sm">
                                        Permissions
                                    </a>
                                @endcan

                                @if($member->id !== auth()->id())
                                    <form method="POST" action="{{ route('staff.toggle', $member) }}"
                                          data-confirm="{{ $member->is_active ? 'Deactivate' : 'Activate' }} this staff account?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn--outline btn--sm">
                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('staff.destroy', $member) }}"
                                          data-confirm="Delete this staff account? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('partials.empty-state', ['title' => 'No staff accounts found'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
