<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('template_title', 'System health')</title>
    @livewireStyles
</head>
<body>
    <main>@yield('content')</main>
    @livewireScripts
    @yield('footer_scripts')
</body>
</html>
