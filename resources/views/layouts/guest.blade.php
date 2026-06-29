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
    <title>{{ config('app.name', 'sablonku') }}</title>
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

    <div class="min-vh-100 d-flex flex-row align-items-center guest-page-bg"
        @if (session()->has('background') && session('background'))
            style="background-image: url('{{ url('uploads/background/' . session('background')) }}'); background-size: cover; background-position: center;"
        @endif>
        <div class="container">
            <div class="row justify-content-center">
                @yield('content')
            </div>
        </div>
    </div>
    @stack('before-scripts')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/coreui.bundle.min.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.js"></script>
    @stack('after-scripts')
</body>

</html>
