<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="{{ asset('assets/Customer/css/style.css') }}">
    <title>Login - Sortkar</title>


</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">Sortkar</div>
            <h1>Sign In</h1>

            @if ($errors->has('email'))
                <div class="error-message">{{ $errors->first('email') }}</div>
            @endif

            <form method="POST" action="/customer/login">
                @csrf

                <label for="email">Email Address <sup>*</sup></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus />

                <label for="password">Password <sup>*</sup></label>
                <input id="password" type="password" name="password" required />

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>
</body>

</html>
