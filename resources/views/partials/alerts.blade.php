@if(session('success'))
    <div class="alert alert--success" role="alert">
        @include('partials.icon', ['name' => 'check'])
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('status'))
    <div class="alert alert--info" role="alert">
        @include('partials.icon', ['name' => 'info'])
        <div>{{ session('status') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert--danger" role="alert">
        @include('partials.icon', ['name' => 'warning'])
        <div>
            <strong>Please review the following:</strong>
            <ul class="alert-list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
