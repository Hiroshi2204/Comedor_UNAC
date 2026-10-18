@if (session('ok'))<div class="aviso ok" role="status">{{ session('ok') }}</div>@endif
@if (session('error'))<div class="aviso error" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())
    <div class="aviso error" role="alert">
        @foreach ($errors->all() as $m)<div>{{ $m }}</div>@endforeach
    </div>
@endif
