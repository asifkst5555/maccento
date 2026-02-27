<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maccento | Sign In</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/media/favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
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
      @if (session('otp_debug_code'))
      <div class="auth-alert auth-alert-info">Debug OTP: {{ session('otp_debug_code') }}</div>
      @endif

      <div class="signin-shell">
        <form class="auth-form" action="{{ route('login.request-otp') }}" method="post">
          @csrf
          <label class="auth-label" for="auth-email-phone">Email or Phone Number</label>
          <input
            id="auth-email-phone"
            class="auth-input auth-input-signin"
            type="text"
            name="identifier"
            value="{{ old('identifier', $identifier ?? '') }}"
            placeholder="you@agency.com or +1 514 000 0000"
            autocomplete="username"
            required
          >
          <button class="auth-btn auth-btn-verify" type="submit" name="channel" value="auto">Send Verification Code</button>
        </form>

        <div class="auth-divider"><span>Choose OTP Channel</span></div>

        <div class="auth-otp-grid auth-otp-request">
          <form action="{{ route('login.request-otp') }}" method="post">
            @csrf
            <input type="hidden" name="identifier" value="{{ old('identifier', $identifier ?? '') }}">
            <button type="submit" class="auth-otp-btn" name="channel" value="sms">Send via SMS</button>
          </form>
          <form action="{{ route('login.request-otp') }}" method="post">
            @csrf
            <input type="hidden" name="identifier" value="{{ old('identifier', $identifier ?? '') }}">
            <button type="submit" class="auth-otp-btn" name="channel" value="email">Send via Email</button>
          </form>
        </div>

        @if (($otpRequested ?? false) || session('otp_requested'))
        <form class="auth-form auth-verify-form" action="{{ route('login.verify-otp') }}" method="post">
          @csrf
          <label class="auth-label" for="auth-otp">Enter OTP</label>
          <input id="auth-otp" class="auth-input auth-input-signin" type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="6-digit code" required>
          <input type="hidden" name="identifier" value="{{ old('identifier', $identifier ?? '') }}">
          <button class="auth-btn auth-btn-verify" type="submit">Verify &amp; Sign In</button>
        </form>
        @endif
      </div>

      <div class="signin-foot">
        <p class="auth-create">Don't have an account? <a href="{{ route('signup') }}">Create Account</a></p>

        <p class="auth-terms">
          By signing up to create an account I accept Company's
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
