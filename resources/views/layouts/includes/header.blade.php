<header class="header header-sticky mb-4">
    <div class="container-fluid d-flex align-items-center">
        <button class="header-toggler px-md-0 me-md-3" type="button"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <svg class="icon icon-lg">
                <use xlink:href="{{ asset('icons/coreui.svg#cil-menu') }}"></use>
            </svg>
        </button>

        <a class="header-brand d-md-none" href="#">
            @if (session()->has('logo'))
                <img style="height:35px"
                    src="{{ url('storage/logo/' . session('logo')[0] . session('logo')[1] . '/' . session('logo')[2] . session('logo')[3] . '/' . session('logo')) }}"
                    alt="{{ config('app.name') }}"
                    srcset="">
            @endif
        </a>

        @auth
            <ul class="header-nav ms-auto d-flex align-items-center gap-1 gap-md-2 flex-nowrap">
                @role('super|Manager')
                    <li class="nav-item">
                        <a class="header-dashboard-btn" href="{{ route('dashboard') }}" aria-label="Dashboard"
                            title="Dashboard">
                            <svg class="icon" style="width:1rem;height:1rem;">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-speedometer') }}"></use>
                            </svg>
                            <span class="d-none d-md-inline">Dashboard</span>
                        </a>
                    </li>
                @endrole

                <li class="nav-item">
                    <button type="button" class="header-theme-toggle" id="theme-toggle"
                        aria-label="Mode gelap" title="Mode gelap">
                        <svg class="icon theme-icon-light" style="width:1rem;height:1rem;">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-moon') }}"></use>
                        </svg>
                        <svg class="icon theme-icon-dark d-none" style="width:1rem;height:1rem;">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-sun') }}"></use>
                        </svg>
                    </button>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link header-user-trigger" data-coreui-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false">
                        <div class="avatar">
                            <img class="avatar-img" src="{{ asset('img/default-avatar.jpg') }}" alt="Avatar">
                        </div>
                        <span class="header-user-name d-none d-lg-inline">{{ Auth::user()->name }}</span>
                        <svg class="icon d-none d-lg-inline" style="width:.75rem;height:.75rem;color:#94a3b8;">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-chevron-bottom') }}"></use>
                        </svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pt-0" style="z-index:1050;min-width:220px;">
                        <div class="px-3 py-2 border-bottom">
                            <div class="fw-semibold" style="font-size:.875rem;">{{ Auth::user()->name }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <div class="p-1">
                            <a class="dropdown-item" href="{{ route('whattodo') }}">
                                <svg class="icon me-2">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-task') }}"></use>
                                </svg>
                                What To Do
                            </a>
                            <a class="dropdown-item" href="{{ route('profile.cuti', Auth::user()->id) }}">
                                <svg class="icon me-2">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-calendar-check') }}"></use>
                                </svg>
                                {{ __('Cuti') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('profile.gaji', Auth::user()->id) }}">
                                <svg class="icon me-2">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-dollar') }}"></use>
                                </svg>
                                {{ __('Gaji') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('profile.lembur', Auth::user()->id) }}">
                                <svg class="icon me-2">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-clock') }}"></use>
                                </svg>
                                {{ __('Lembur') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('profile.show', Auth::user()->id) }}">
                                <svg class="icon me-2">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-lock-locked') }}"></use>
                                </svg>
                                {{ __('Ganti Password') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    <svg class="icon me-2">
                                        <use xlink:href="{{ asset('icons/coreui.svg#cil-account-logout') }}"></use>
                                    </svg>
                                    {{ __('Logout') }}
                                </a>
                            </form>
                        </div>
                    </div>
                </li>
            </ul>
        @endauth
    </div>
</header>
