<!doctype html>
<html><head><meta charset="utf-8"><title>Register</title></head>
<body>
  <h1>Register</h1>
  <form method="POST" action="{{ route('register') }}">
    @csrf
    <div><input name="name" placeholder="Name" value="{{ old('name') }}" required></div>
    <div><input name="email" type="email" placeholder="Email" value="{{ old('email') }}" required></div>
    <div><input name="password" type="password" placeholder="Password" required></div>
    <div><input name="password_confirmation" type="password" placeholder="Confirm Password" required></div>
    <div><button type="submit">Register</button></div>
  </form>
</body></html>
