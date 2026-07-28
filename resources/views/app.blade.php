<!DOCTYPE html>
<html lang="vi" class="__roots root__page">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ngọc Rồng Horizon</title>
    <link rel="shortcut icon" type="image/png" href="/assets/pixel/brand-orb.png" />

    <!-- Readable Vietnamese UI font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <!-- Legacy page layout styles. Pixel theme from Vite is loaded after these. -->
    <link rel="stylesheet" href="/assets/css/pages.css?v={{ filemtime(public_path('assets/css/pages.css')) }}" />
    <link rel="stylesheet" href="/assets/css/auth.css?v={{ filemtime(public_path('assets/css/auth.css')) }}" />
    @vite('resources/js/app.js')
</head>

<body>
    <div id="app"></div>
</body>

</html>
