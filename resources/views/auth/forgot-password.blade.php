<!doctype html><html><head><meta charset="utf-8"><title>Forgot Password</title></head><body>
  <h1>Forgot Password</h1>
  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div><input name="email" type="email" placeholder="Your email" required></div>
    <div><button type="submit">Send Reset Link</button></div>
  </form>
</body></html>
