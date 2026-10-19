@if (session('success'))
    <div class="alert success" role="status">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert danger" role="alert">
        {{ session('error') }}
    </div>
@endif

@if (session('info'))
    <div class="alert info" role="status">
        {{ session('info') }}
    </div>
@endif

@if (session('warning'))
    <div class="alert warning" role="status">
        {{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert danger" role="alert">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
