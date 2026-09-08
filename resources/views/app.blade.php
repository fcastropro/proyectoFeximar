<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <link
            rel="stylesheet"
            href="/admin-assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css"
        >

        <link
            rel="stylesheet"
            href="/admin-assets/css/style.css"
        >

        <link
            rel="stylesheet"
            href="/admin-assets/vendor/metismenu/css/metisMenu.min.css"
        >

        <link
            rel="stylesheet"
            href="/admin-assets/css/feximar-theme.css"
        >
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
