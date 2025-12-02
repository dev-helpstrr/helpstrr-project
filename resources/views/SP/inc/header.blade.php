<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/brand_images/favicon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/SP/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/SP/css/style.css') }}">
     <link rel="stylesheet" href="{{ asset('assets/SP/css/owl.carousel.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/sweetalert2/sweetalert2.min.css') }}">
    <script src="{{ asset('js/sweetalert2/sweetalert2.min.js') }}"></script>


    {{-- <link rel="stylesheet" href="{{ asset('css/font-awesome/font-awesome.min.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">


    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> --}}

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

   <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400..700&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>{{env('APP_NAME')}} – Find Reliable Help for Your Daily Needs</title>
</head>

<body>
