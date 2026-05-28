<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'FisioVida') }}</title>

        <meta name="description" content="FisioVida — Sistema de gestión clínica para fisioterapia. Agenda, expedientes, sesiones, pagos y portal para pacientes.">
        <meta name="robots" content="noindex, nofollow">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'FisioVida') }}">
        <meta property="og:title" content="{{ config('app.name', 'FisioVida') }} — Gestión clínica para fisioterapia">
        <meta property="og:description" content="Plataforma integral para clínicas de fisioterapia: agenda inteligente, expedientes clínicos, control de pagos y portal del paciente.">
        <meta property="og:image" content="{{ asset('favicon.ico') }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'FisioVida') }}">
        <meta name="twitter:description" content="Sistema de gestión clínica para fisioterapia.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
