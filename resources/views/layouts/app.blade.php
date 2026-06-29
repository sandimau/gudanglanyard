<!DOCTYPE html>
<html lang="id">

<head>
    <script>
        (function() {
            var storageKey = 'app-theme';
            var theme = localStorage.getItem(storageKey);

            if (theme === 'light' && !localStorage.getItem('theme-default-dark-v1')) {
                theme = 'dark';
                localStorage.setItem(storageKey, 'dark');
                localStorage.setItem('theme-default-dark-v1', '1');
            }

            if (theme !== 'light') {
                theme = 'dark';
            }

            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>
        @if (trim($__env->yieldContent('title')))
            @yield('title') | {{ config('app.name') }}
        @else
            {{ config('app.name') }}
        @endif
    </title>
    <meta name="theme-color" content="#0f172a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @stack('before-styles')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    @stack('after-styles')
</head>

<body>
    <div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
        <div class="sidebar-brand d-none d-md-flex">
            @if (session()->has('Logo'))
                <img style="height:50px" src="{{ url('uploads/Logo/' . session('Logo')) }}"
                    alt="{{ config('app.name') }}" srcset="">
            @endif
        </div>
        @include('layouts.navigation')
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
    <div class="wrapper d-flex flex-column min-vh-100">
        @include('layouts.includes.header')

        <div class="body flex-grow-1 px-3">
            <div class="container-fluid">
                @include('layouts.includes.errors')
                <div class="mb-4">@yield('content')</div>
            </div>
        </div>

        @include('layouts.includes.footer')
    </div>

    <div class="modal fade" id="modal-hapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="post" class="d-flex gap-2 w-100 justify-content-end">
                        {{ csrf_field() }}
                        {{ method_field('delete') }}
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @stack('before-scripts')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/coreui.bundle.min.js') }}"></script>
    <script src="{{ asset('js/jquery.PrintArea.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous">
    </script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.js"></script>
    @stack('after-scripts')

</body>

</html>
