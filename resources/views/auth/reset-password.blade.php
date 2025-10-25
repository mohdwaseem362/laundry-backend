<!doctype html><html><head><meta charset="utf-8"><title>Reset Password</title></head><body>
  <h1>Reset Password</h1>
  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div><input name="email" type="email" value="{{ old('email', $request->email) }}" required></div>
    <div><input name="password" type="password" required></div>
    <div><input name="password_confirmation" type="password" required></div>
    <div><button type="submit">Reset</button></div>
  </form>
</body></html>
