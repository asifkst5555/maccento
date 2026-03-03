<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maccento | Sign In</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/media/favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v={{ @filemtime(public_path('assets/css/site.css')) ?: time() }}">
</head>
<body class="auth-page auth-page-signin">
  <main class="auth-wrap auth-wrap-signin">
    <section class="auth-card auth-card-signin">
      <div class="signin-head">
        <p class="auth-kicker">Maccento Client Portal</p>
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to access bookings, projects, and delivery files.</p>
      </div>

      @if (session('status'))
      <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
      <div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
      @endif

      <form class="auth-form" method="post" action="{{ route('login.store') }}">
        @csrf
        <label class="auth-label" for="auth-email">Email Address</label>
        <input id="auth-email" class="auth-input auth-input-signin" type="email" name="email" value="{{ old('email') }}" placeholder="you@agency.com" required>

        <label class="auth-label" for="auth-password">Password</label>
        <input id="auth-password" class="auth-input auth-input-signin" type="password" name="password" placeholder="Enter your password" required>

        <label class="auth-check"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="auth-btn auth-btn-verify" type="submit">Sign In</button>
      </form>

      <div class="signin-foot">
        <p class="auth-create">Don't have an account? <a href="{{ route('signup') }}">Create Account</a></p>
        <p class="auth-terms">
          By signing in you accept Company's
          <a href="#">Terms of use</a> &amp; <a href="#">Privacy Policy</a>.
        </p>
      </div>
    </section>

    <aside class="auth-media auth-media--signin">
      <img loading="eager" decoding="async" src="{{ asset('assets/media/package-3.webp') }}" alt="Luxury real estate exterior">
      <a class="auth-pricing" href="{{ route('plan') }}">See Pricing</a>
    </aside>
  </main>
</body>
</html>
