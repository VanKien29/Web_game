<!DOCTYPE html>
<html lang="vi" class="__roots root__page">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ngọc Rồng Horizon</title>
    <link rel="shortcut icon" type="image/png" href="/assets/frontend/home/v1/images/favicon.png" />

    <!-- Readable Vietnamese UI font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

    <!-- Library CSS -->
    <link rel="stylesheet" href="/assets/frontend/home/v1/css/slick-theme.css" />
    <link rel="stylesheet" href="/assets/frontend/home/v1/css/slick.css" />
    <link rel="stylesheet" href="/assets/frontend/home/v1/css/jquery.fancybox.min.css" />
    <link rel="stylesheet" href="/assets/frontend/home/v1/css/aos.css" />

    <!-- Main game stylesheet -->
    <link rel="stylesheet" href="/assets/frontend/home/v1/css/stylea6ca.css" />

    <!-- Custom CSS (body, fonts, footer, sidebar, popup) -->
    <link rel="stylesheet" href="/assets/css/game.css?v={{ filemtime(public_path('assets/css/game.css')) }}" />
    <link rel="stylesheet" href="/assets/css/pages.css?v={{ filemtime(public_path('assets/css/pages.css')) }}" />
    <link rel="stylesheet" href="/assets/css/auth.css?v={{ filemtime(public_path('assets/css/auth.css')) }}" />
    @vite('resources/js/app.js')
</head>

<body>
    <div id="app"></div>

    <!-- jQuery & plugins -->
    <script defer src="/assets/frontend/home/v1/js/jquery.min.js"></script>
    <script defer src="/assets/frontend/home/v1/js/ScrollMagic.min.js"></script>
    <script defer src="/assets/frontend/home/v1/js/aos.js"></script>
    <script defer src="/assets/frontend/home/v1/js/slick.min.js"></script>
    <script defer src="/assets/frontend/home/v1/js/jquery.fancybox.min.js"></script>
    <script defer src="/assets/js/main.js"></script>
</body>

</html>
