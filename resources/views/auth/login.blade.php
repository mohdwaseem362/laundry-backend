<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
    <h1>Login</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div><label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div><label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div><label><input type="checkbox" name="remember"> Remember</label></div>
        <div><button type="submit">Login</button></div>
    </form>
    <p><a href="{{ route('password.request') }}">Forgot password?</a></p>
    <p><a href="{{ route('register') }}">Register</a></p>
</body>
</html>
