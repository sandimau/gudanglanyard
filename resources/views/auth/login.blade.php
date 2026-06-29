@extends('layouts.guest')

@section('content')
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    @if (session()->has('Logo') && session('Logo'))
                        <img src="{{ url('uploads/Logo/' . session('Logo')) }}" alt="{{ config('app.name') }}"
                            class="mb-3" style="height:80px;max-width:200px;object-fit:contain;">
                    @else
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                            style="width:56px;height:56px;background:var(--app-primary-light,#eef2ff);">
                            <svg class="icon" style="width:1.5rem;height:1.5rem;color:var(--app-primary,#6366f1);">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-lock-locked') }}"></use>
                            </svg>
                        </div>
                    @endif
                    <h1 class="h4 fw-bold mb-1">{{ __('Login') }}</h1>
                    <p class="text-muted mb-0" style="font-size:.875rem;">Masuk ke akun {{ config('app.name') }}</p>
                </div>

                @include('layouts.includes.errors')

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">{{ __('Username') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <svg class="icon text-muted">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-user') }}"></use>
                                </svg>
                            </span>
                            <input class="form-control border-start-0 @error('username') is-invalid @enderror"
                                id="email" type="text" name="email" placeholder="Masukkan username"
                                required autofocus>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password">{{ __('Password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <svg class="icon text-muted">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-lock-locked') }}"></use>
                                </svg>
                            </span>
                            <input class="form-control border-start-0 @error('password') is-invalid @enderror"
                                id="password" type="password" name="password" placeholder="Masukkan password"
                                required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
                        {{ __('Login') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
