<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maccento | Create Account</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/media/favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
</head>
<body class="auth-page auth-page-signup">
  <main class="auth-wrap auth-wrap-signup">
    <section class="auth-card auth-card-signup">
      <p class="auth-kicker">Maccento Client Portal</p>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Set up your account to book and manage projects.</p>

      @if ($errors->any())
      <div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
      @endif

      <form class="auth-form auth-register-form" action="{{ route('signup.store') }}" method="post">
        @csrf
        <label class="auth-label" for="auth-name">Full Name</label>
        <input id="auth-name" class="auth-input auth-input-md" type="text" name="name" value="{{ old('name') }}" placeholder="Your full name" required>

        <label class="auth-label" for="auth-email">Email Address</label>
        <input id="auth-email" class="auth-input auth-input-md" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required>

        <label class="auth-label" for="auth-phone">Phone (optional)</label>
        <input id="auth-phone" class="auth-input auth-input-md" type="text" name="phone" value="{{ old('phone') }}" placeholder="+1 514 000 0000">

        <button class="auth-btn auth-btn-verify" type="submit">Create Account</button>
      </form>

      <p class="auth-create">Already have an account? <a href="{{ route('login') }}">Sign In</a></p>

      <p class="auth-terms">
        By creating an account you agree to our
        <a href="#">Terms of use</a> &amp; <a href="#">Privacy Policy</a>.
      </p>
    </section>

    <aside class="auth-media auth-media--signup">
      <img loading="eager" decoding="async" src="{{ asset('assets/media/package-1.webp') }}" alt="Luxury property interior">
      <a class="auth-pricing" href="{{ route('plan') }}">See Pricing</a>
    </aside>
  </main>
</body>
</html>
