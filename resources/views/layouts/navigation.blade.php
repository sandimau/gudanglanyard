@php
    $navOpen = fn(...$patterns) => collect($patterns)->contains(fn($p) => request()->is($p));

    $orderKeuanganPaths = ['admin/order/belumLunas*'];
    $orderOmzetPaths = ['admin/order/omzet*'];
    $orderProduksiExcluded = [
        'admin/order/dashboard*',
        'admin/order/marketplace*',
        'admin/order/belumLunas*',
        'admin/order/omzet*',
        'admin/order/arsip*',
    ];

    $activeOrderProses = request()->is('admin/order/dashboard*');
    $activeOrderArsip =
        request()->is('admin/order') || (request()->is('admin/order/*') && !request()->is(...$orderProduksiExcluded));
    $activeOrderOnline = request()->is('admin/order/marketplace*');
    $activeBelumLunas = request()->is(...$orderKeuanganPaths);
    $activeOmzetTahunan = request()->is('admin/order/omzet') || request()->is('admin/order/omzet/*');
    $activeOmzetBulanan = request()->is('admin/order/omzetBulan*');
    $activeOmzetMarketplace = request()->is('admin/marketplace/omzetBulan*');

    $activeMarketplaceAnalisa = request()->is('admin/analisaMarketplace*');
    $activeMarketplaceSyncStok = request()->is('admin/marketplaceSyncStok*');
    $activeAnalisaBeban = request()->is('admin/analisa/beban*');
    $activeAnalisaOperasional = request()->is('admin/analisa/operasional*');
    $activeAnalisaStok = request()->is('admin/analisa/stok*');

    $openProduksiOrder = $activeOrderProses || $activeOrderArsip || $activeOrderOnline;
    $openData = $navOpen('admin/kontaks*');
    $openKeuangan =
        $navOpen('admin/akunKategoris*', 'admin/akunDetails*', 'admin/belanja*', 'admin/hutang*', 'admin/kas') ||
        $activeBelumLunas;
    $openMarketplace =
        $navOpen('admin/projectmp*', 'admin/marketplaceProduk*', 'admin/marketplaces*', 'admin/marketplaceSyncStok*') ||
        $activeMarketplaceAnalisa;
    $openInventory = $navOpen('admin/produk-kategori-utama*', 'admin/pemakaian*', 'admin/opnames*', 'admin/po*');
    $openProduksiFactory = $navOpen('admin/produksi*', 'admin/produkProduksi*') && !$navOpen('admin/produksis*');
    $openPegawai = $navOpen('admin/members*', 'admin/freelance*', 'admin/absensi*', 'admin/ars*');
    $openAnalisa = $activeAnalisaBeban || $activeAnalisaOperasional || $activeAnalisaStok;
    $openLaporan = $navOpen(
        'admin/neraca*',
        'admin/labarugi*',
        'admin/labakotor*',
        'admin/tunjangan*',
        'admin/penggajian*',
        'admin/operasional*',
    );
    $openOmzet =
        $activeOmzetTahunan ||
        $activeOmzetBulanan ||
        $activeOmzetMarketplace ||
        $navOpen('admin/aset*', 'admin/produk/omzet*');
    $openUserMgmt = $navOpen('users*', 'admin/level*', 'admin/bagian*');
    $openConfig = $navOpen(
        'roles*',
        'permissions*',
        'admin/produksis*',
        'admin/speks*',
        'admin/pemproses*',
        'admin/sistem*',
        'admin/linkPages*',
    );

    $user = auth()->user();
    $showProduksiOrder = $user->can('order_access');
    $showData = $user->can('kontak_access');
    $showKeuangan =
        $user->can('akun_detail_access') ||
        $user->can('keuangan') ||
        ($user->hasRole('super') && $user->can('akun_access'));
    $showMarketplace = $user->can('marketplace_access');
    $showInventory = $user->can('produk_access');
    $showProduksiFactory = $user->can('produk_access');
    $showPegawai = $user->can('member_access');
    $showAnalisa = $user->can('laporan_access');
    $showLaporan = $user->can('laporan_access');
    $showOmzet = $user->can('omzet_access');
    $showUserMgmt = $user->hasAnyPermission(['user_access', 'level_access', 'bagian_access']);
    $showConfig = true;
@endphp

<ul class="sidebar-nav compact" data-coreui="navigation" data-simplebar>
    @if ($showProduksiOrder)
        <li class="nav-group{{ $openProduksiOrder ? ' show' : '' }}"
            aria-expanded="{{ $openProduksiOrder ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-industry') }}"></use>
                </svg>
                Proses Order
            </a>
            <ul class="nav-group-items">
                @can('order_access')
                    <li class="nav-item">
                        <a class="nav-link {{ $activeOrderProses ? 'active' : '' }}" href="{{ route('order.dashboard') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-task') }}"></use>
                            </svg>
                            Proses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeOrderArsip ? 'active' : '' }}" href="{{ route('order.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-folder-open') }}"></use>
                            </svg>
                            Arsip Offline
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeOrderOnline ? 'active' : '' }}"
                            href="{{ route('order.marketplace') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-cloud-download') }}"></use>
                            </svg>
                            Arsip Online
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showMarketplace)
        <li class="nav-group{{ $openMarketplace ? ' show' : '' }}"
            aria-expanded="{{ $openMarketplace ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-basket') }}"></use>
                </svg>
                Marketplace
            </a>
            <ul class="nav-group-items">
                @can('marketplace_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/projectmp/dashboard*') ? 'active' : '' }}"
                            href="{{ route('projectmp.dashboard') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-speedometer') }}"></use>
                            </svg>
                            {{ __('Proses Custom') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/projectmp/packing*') ? 'active' : '' }}"
                            href="{{ route('projectmp.packing') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-truck') }}"></use>
                            </svg>
                            {{ __('Proses Packing') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/projectmp/index*') ? 'active' : '' }}"
                            href="{{ route('projectmp.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-folder-open') }}"></use>
                            </svg>
                            {{ __('Arsip Order') }}
                        </a>
                    </li>
                @endcan
                @can('marketplace_config')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/marketplaceProduk*') ? 'active' : '' }}"
                            href="{{ route('marketplaces.produk') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-tags') }}"></use>
                            </svg>
                            {{ __('Produk') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('marketplaces*') ? 'active' : '' }}"
                            href="{{ route('marketplaces.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-settings') }}"></use>
                            </svg>
                            {{ __('Config') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeMarketplaceSyncStok ? 'active' : '' }}"
                            href="{{ route('marketplaces.syncStokStatus') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-sync') }}"></use>
                            </svg>
                            {{ __('Sync Stok Shopee') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeMarketplaceAnalisa ? 'active' : '' }}"
                            href="{{ route('marketplaces.analisa') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-chart-line') }}"></use>
                            </svg>
                            {{ __('Analisa') }}
                        </a>
                    </li>
                @endcan

            </ul>
        </li>
    @endif

    @if ($showData)
        <li class="nav-group{{ $openData ? ' show' : '' }}" aria-expanded="{{ $openData ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-storage') }}"></use>
                </svg>
                Data
            </a>
            <ul class="nav-group-items">
                @can('kontak_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('kontaks*') ? 'active' : '' }}"
                            href="{{ route('kontaks.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-user') }}"></use>
                            </svg>
                            {{ __('Kontak') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showKeuangan)
        <li class="nav-group{{ $openKeuangan ? ' show' : '' }}"
            aria-expanded="{{ $openKeuangan ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-dollar') }}"></use>
                </svg>
                Keuangan
            </a>
            <ul class="nav-group-items">
                @role('super')
                    @can('akun_access')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('akunKategoris*') ? 'active' : '' }}"
                                href="{{ route('akunDetails.index') }}">
                                <svg class="nav-icon">
                                    <use xlink:href="{{ asset('icons/coreui.svg#cil-calculator') }}"></use>
                                </svg>
                                {{ __('akuns') }}
                            </a>
                        </li>
                    @endcan
                @endrole
                @can('akun_detail_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('akunDetails*') ? 'active' : '' }}"
                            href="{{ route('akunDetail.kas') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-wallet') }}"></use>
                            </svg>
                            {{ __('kas') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeBelumLunas ? 'active' : '' }}" href="{{ route('order.unpaid') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-credit-card') }}"></use>
                            </svg>
                            {{ __('belum lunas') }}
                        </a>
                    </li>
                @endcan
                @can('keuangan')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('belanjas*') ? 'active' : '' }}"
                            href="{{ route('belanja.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-cart') }}"></use>
                            </svg>
                            Belanja
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('hutang*') ? 'active' : '' }}"
                            href="{{ route('hutang.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-file') }}"></use>
                            </svg>
                            Hutang/Piutang
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showInventory)
        <li class="nav-group{{ $openInventory ? ' show' : '' }}"
            aria-expanded="{{ $openInventory ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-inbox') }}"></use>
                </svg>
                Inventory
            </a>
            <ul class="nav-group-items">
                @can('produk_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('produk-kategori-utama*') ? 'active' : '' }}"
                            href="{{ route('produk-kategori-utama.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-basket') }}"></use>
                            </svg>
                            {{ __('Produk') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('pemakaian*') ? 'active' : '' }}"
                            href="{{ route('pemakaian.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-basket') }}"></use>
                            </svg>
                            Pemakaian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('opnames*') ? 'active' : '' }}"
                            href="{{ route('opnames.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-clipboard') }}"></use>
                            </svg>
                            {{ __('Opname') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('po*') ? 'active' : '' }}" href="{{ route('po.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-description') }}"></use>
                            </svg>
                            {{ __('PO') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showProduksiFactory)
        <li class="nav-group{{ $openProduksiFactory ? ' show' : '' }}"
            aria-expanded="{{ $openProduksiFactory ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-inbox') }}"></use>
                </svg>
                Produksi
            </a>
            <ul class="nav-group-items">
                @can('produk_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('produksi*') ? 'active' : '' }}"
                            href="{{ route('produksi.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-factory') }}"></use>
                            </svg>
                            Proses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('produksi*') ? 'active' : '' }}"
                            href="{{ route('produkProduksi.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-factory') }}"></use>
                            </svg>
                            Produk
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showPegawai)
        <li class="nav-group{{ $openPegawai ? ' show' : '' }}"
            aria-expanded="{{ $openPegawai ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-people') }}"></use>
                </svg>
                Pegawai
            </a>
            <ul class="nav-group-items">
                @can('member_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('members*') ? 'active' : '' }}"
                            href="{{ route('members.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-user') }}"></use>
                            </svg>
                            Karyawan
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('members*') ? 'active' : '' }}"
                            href="{{ route('members.freelance') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-user-follow') }}"></use>
                            </svg>
                            Freelance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}"
                            href="{{ route('absensi.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-calendar') }}"></use>
                            </svg>
                            Absensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('ars*') ? 'active' : '' }}"
                            href="{{ route('ars.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-headphones') }}"></use>
                            </svg>
                            {{ __('cs') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showAnalisa)
        <li class="nav-group{{ $openAnalisa ? ' show' : '' }}"
            aria-expanded="{{ $openAnalisa ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-chart') }}"></use>
                </svg>
                Analisa
            </a>
            <ul class="nav-group-items">
                @can('laporan_access')
                    <li class="nav-item">
                        <a class="nav-link {{ $activeAnalisaBeban ? 'active' : '' }}"
                            href="{{ route('analisa.beban') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-chart-line') }}"></use>
                            </svg>
                            Analisa Beban
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeAnalisaOperasional ? 'active' : '' }}"
                            href="{{ route('analisa.operasional') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-bar-chart') }}"></use>
                            </svg>
                            Analisa Operasional
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeAnalisaStok ? 'active' : '' }}"
                            href="{{ route('analisa.stok') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-chart-pie') }}"></use>
                            </svg>
                            Analisa Stok
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showLaporan)
        <li class="nav-group{{ $openLaporan ? ' show' : '' }}"
            aria-expanded="{{ $openLaporan ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-description') }}"></use>
                </svg>
                Laporan
            </a>
            <ul class="nav-group-items">
                @can('laporan_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}"
                            href="{{ route('laporan.tunjangan') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-dollar') }}"></use>
                            </svg>
                            {{ __('Tunjangan') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}"
                            href="{{ route('laporan.penggajian') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-wallet') }}"></use>
                            </svg>
                            {{ __('Penggajian') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}"
                            href="{{ route('laporan.neraca') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-balance-scale') }}"></use>
                            </svg>
                            {{ __('Neraca') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}"
                            href="{{ route('laporan.labarugi') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-chart-line') }}"></use>
                            </svg>
                            {{ __('Laba Rugi') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showOmzet)
        <li class="nav-group{{ $openOmzet ? ' show' : '' }}" aria-expanded="{{ $openOmzet ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-graph') }}"></use>
                </svg>
                Omzet
            </a>
            <ul class="nav-group-items">
                @can('omzet_access')
                    <li class="nav-item">
                        <a class="nav-link {{ $activeOmzetTahunan ? 'active' : '' }}"
                            href="{{ route('order.omzet') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-calendar') }}"></use>
                            </svg>
                            {{ __('Tahunan') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ $activeOmzetBulanan ? 'active' : '' }}"
                            href="{{ route('order.omzetBulan') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-calendar') }}"></use>
                            </svg>
                            {{ __('Bulanan') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ $activeOmzetMarketplace ? 'active' : '' }}"
                            href="{{ route('marketplaces.omzetBulan') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-basket') }}"></use>
                            </svg>
                            {{ __('Marketplace') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('produk*') ? 'active' : '' }}"
                            href="{{ route('produk.aset') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-building') }}"></use>
                            </svg>
                            {{ __('Aset') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('produk*') ? 'active' : '' }}"
                            href="{{ route('produk.omzet') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-chart-line') }}"></use>
                            </svg>
                            {{ __('Produk Omzet') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showUserMgmt)
        <li class="nav-group{{ $openUserMgmt ? ' show' : '' }}"
            aria-expanded="{{ $openUserMgmt ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-people') }}"></use>
                </svg>
                User Management
            </a>
            <ul class="nav-group-items">

                @can('user_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                            href="{{ route('users.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-user') }}"></use>
                            </svg>
                            {{ __('Users') }}
                        </a>
                    </li>
                @endcan

                @can('level_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('levels*') ? 'active' : '' }}"
                            href="{{ route('level.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-layers') }}"></use>
                            </svg>
                            {{ __('Levels') }}
                        </a>
                    </li>
                @endcan

                @can('bagian_access')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('bagians*') ? 'active' : '' }}"
                            href="{{ route('bagian.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-sitemap') }}"></use>
                            </svg>
                            {{ __('Bagians') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endif

    @if ($showConfig)
        <li class="nav-group{{ $openConfig ? ' show' : '' }}"
            aria-expanded="{{ $openConfig ? 'true' : 'false' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="{{ asset('icons/coreui.svg#cil-cog') }}"></use>
                </svg>
                Config
            </a>
            <ul class="nav-group-items">
                @role('super')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('roles*') ? 'active' : '' }}"
                            href="{{ route('roles.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-group') }}"></use>
                            </svg>
                            {{ __('Roles') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('permissions*') ? 'active' : '' }}"
                            href="{{ route('permissions.index') }}">
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('icons/coreui.svg#cil-lock-locked') }}"></use>
                            </svg>
                            {{ __('Permissions') }}
                        </a>
                    </li>
                @endrole

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('produksis*') ? 'active' : '' }}"
                        href="{{ route('produksis.index') }}">
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-wrench') }}"></use>
                        </svg>
                        {{ __('Setup Produksi') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('speks*') ? 'active' : '' }}"
                        href="{{ route('speks.index') }}">
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-list') }}"></use>
                        </svg>
                        {{ __('Spek Produk') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('pemproses*') ? 'active' : '' }}"
                        href="{{ route('pemproses.index') }}">
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-factory') }}"></use>
                        </svg>
                        {{ __('Pemproses') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('sistems*') ? 'active' : '' }}"
                        href="{{ route('sistem.index') }}">
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-settings') }}"></use>
                        </svg>
                        {{ __('Sistem') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/linkPages*') ? 'active' : '' }}"
                        href="{{ route('linkPages.index') }}">
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('icons/coreui.svg#cil-link') }}"></use>
                        </svg>
                        {{ __('Link Pages') }}
                    </a>
                </li>
            </ul>
        </li>
    @endif
</ul>

@push('after-scripts')
    <script>
        window.addEventListener('load', function() {
            document.querySelectorAll('.sidebar-nav .nav-group').forEach(function(group) {
                if (!group.querySelector('.nav-group-items .nav-link.active')) {
                    return;
                }

                group.classList.add('show');
                group.setAttribute('aria-expanded', 'true');

                var items = group.querySelector('.nav-group-items');
                if (items) {
                    items.style.removeProperty('height');
                }
            });
        });
    </script>
@endpush
