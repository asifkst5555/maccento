<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'CRM Panel' }}</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/media/favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v={{ @filemtime(public_path('assets/css/site.css')) ?: time() }}">
</head>
<body class="panel-page">
  @auth
    @php
      $panelRole = strtolower((string) auth()->user()->role);
      $isInternalRole = in_array($panelRole, ['admin', 'owner', 'manager', 'photographer', 'editor'], true);
      $canManageUsers = in_array($panelRole, ['owner', 'admin'], true);
      $accessLabel = match ($panelRole) {
        'owner' => 'Owner Access',
        'admin' => 'Admin Access',
        'manager' => 'Manager Access',
        'photographer' => 'Photographer Access',
        'editor' => 'Editor Access',
        'agent' => 'Agent Access',
        'client' => 'Client Access',
        default => 'User Access',
      };
    @endphp
  @endauth

  <div class="panel-app" id="panel-app">
    <aside class="panel-sidebar" id="panel-sidebar">
      <div class="panel-brand">
        <div class="panel-brand-logo" aria-label="Maccento CRM brand mark">
          <span class="panel-brand-logo-m">M</span><span class="panel-brand-logo-c">C</span>
        </div>
        <div class="panel-brand-meta">
          <p class="panel-brand-name">Maccento CRM</p>
          @auth
          <p class="panel-brand-role">{{ strtoupper((string) auth()->user()->role) }}</p>
          @endauth
        </div>
        <button class="panel-collapse-toggle" type="button" aria-label="Collapse sidebar" data-panel-collapse>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>

      <nav class="panel-nav">
        @auth
          @if ($isInternalRole)
          <p class="panel-nav-group-title">Overview</p>
          <a class="panel-nav-link @if(request()->routeIs('admin.dashboard')) is-active @endif" href="{{ route('admin.dashboard') }}" title="Dashboard">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9zm10 7h6v-9h-6v9zM4 20h6v-5H4v5zm10-11h6V4h-6v5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Dashboard</span>
          </a>

          @if(in_array($panelRole, ['admin', 'owner', 'manager'], true))
          <p class="panel-nav-group-title">Lead Management</p>
          @php
            $leadNavCounts = cache()->remember('panel_lead_nav_counts', now()->addSeconds(30), static function (): array {
              return [
                'all' => \App\Models\LeadProfile::query()->count(),
                'ai' => \App\Models\LeadProfile::query()->whereHas('conversation', function ($query): void {
                  $query->where('channel', 'website_widget');
                })->count(),
                'packages' => \App\Models\LeadProfile::query()->whereHas('conversation', function ($query): void {
                  $query->where('channel', 'package_builder');
                })->count(),
                'submissions' => \App\Models\WebsiteFormSubmission::query()->count(),
              ];
            });
          @endphp
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.*') && !request()->routeIs('admin.leads.ai.*') && !request()->routeIs('admin.leads.packages.*')) is-active @endif" href="{{ route('admin.leads.index') }}" title="All Leads">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">All Leads</span>
            <span class="panel-nav-count">{{ number_format((int) ($leadNavCounts['all'] ?? 0)) }}</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.ai.*')) is-active @endif" href="{{ route('admin.leads.ai.index') }}" title="Leads from AI Assistance">
            <span class="panel-nav-icon"><img src="{{ asset('assets/media/icon/ai_icon.png') }}" alt="" aria-hidden="true"></span>
            <span class="panel-nav-text">Leads from AI Assistance</span>
            <span class="panel-nav-count">{{ number_format((int) ($leadNavCounts['ai'] ?? 0)) }}</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.packages.*')) is-active @endif" href="{{ route('admin.leads.packages.index') }}" title="Leads from Packages">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7.5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v4a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-4H5a2 2 0 0 1-2-2v-3zm5 5v4h8v-4H8zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Leads from Packages</span>
            <span class="panel-nav-count">{{ number_format((int) ($leadNavCounts['packages'] ?? 0)) }}</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.form-submissions*')) is-active @endif" href="{{ route('admin.form-submissions') }}" title="Website Submissions">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14a2 2 0 0 1 2 2v14l-4-3-4 3-4-3-4 3V5a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
            <span class="panel-nav-text">Website Submissions</span>
            <span class="panel-nav-count">{{ number_format((int) ($leadNavCounts['submissions'] ?? 0)) }}</span>
          </a>

          <p class="panel-nav-group-title">Sales Operations</p>
          <a class="panel-nav-link @if(request()->routeIs('admin.quotes.*')) is-active @endif" href="{{ route('admin.quotes.index') }}" title="Quote Pipeline">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4V5zm3 3v2h10V8H7zm0 4v2h6v-2H7z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Quote Pipeline</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.invoices.*')) is-active @endif" href="{{ route('admin.invoices.index') }}" title="Invoices">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm8 1.5V9h4.5M8 13h8m-8 3h8m-8-6h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Invoices</span>
          </a>
          @php
            $composeActive = request()->routeIs('admin.emails.inbox') && (string) request()->query('compose') === '1';
            $automationActive = request()->routeIs('admin.emails.automation.*');
            $emailNavCounts = cache()->remember('panel_email_nav_counts', now()->addSeconds(30), static function (): array {
              return [
                'inbox' => \App\Models\InboundEmail::query()->count(),
                'sent' => \App\Models\EmailLog::query()->count(),
                'drafts' => \App\Models\EmailDraft::query()->where('status', 'draft')->count(),
              ];
            });
            $emailNavTotal = (int) (($emailNavCounts['inbox'] ?? 0) + ($emailNavCounts['sent'] ?? 0) + ($emailNavCounts['drafts'] ?? 0));
          @endphp
          <div class="panel-nav-link-group @if(request()->routeIs('admin.emails.*')) is-active @endif" data-subnav-group="emails">
            <div class="panel-nav-head">
              <a class="panel-nav-link @if(request()->routeIs('admin.emails.*')) is-active @endif" href="{{ route('admin.emails.inbox') }}" title="Email Center">
                <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6zm2 .5V8l7 4.7L19 8V6.5l-7 4.6-7-4.6z" fill="currentColor"/></svg></span>
                <span class="panel-nav-text">Email Center</span>
                <span class="panel-nav-count">{{ number_format($emailNavTotal) }}</span>
              </a>
              <button class="panel-subnav-toggle" type="button" aria-label="Toggle Email Center menu" aria-expanded="true" data-subnav-toggle="emails">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M6 8l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
            <div class="panel-subnav" data-subnav="emails">
              <a class="panel-subnav-link @if($composeActive) is-active @endif" href="{{ route('admin.emails.inbox', ['compose' => 1]) }}">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <span>Compose</span>
              </a>
              <a class="panel-subnav-link @if(request()->routeIs('admin.emails.inbox') && !$composeActive) is-active @endif" href="{{ route('admin.emails.inbox') }}">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6zm2 .5v1.2l5 3.2 5-3.2V6.5l-5 3.1-5-3.1z" fill="currentColor"/></svg></span>
                <span>Inbox</span>
                <span class="panel-subnav-count">{{ number_format((int) ($emailNavCounts['inbox'] ?? 0)) }}</span>
              </a>
              <a class="panel-subnav-link @if(request()->routeIs('admin.emails.sent')) is-active @endif" href="{{ route('admin.emails.sent') }}">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                <span>Sent</span>
                <span class="panel-subnav-count">{{ number_format((int) ($emailNavCounts['sent'] ?? 0)) }}</span>
              </a>
              <a class="panel-subnav-link @if(request()->routeIs('admin.emails.drafts')) is-active @endif" href="{{ route('admin.emails.drafts') }}">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Drafts</span>
                <span class="panel-subnav-count">{{ number_format((int) ($emailNavCounts['drafts'] ?? 0)) }}</span>
              </a>
              <a class="panel-subnav-link @if($automationActive) is-active @endif" href="{{ route('admin.emails.automation.index') }}">
                <span class="panel-subnav-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3.5l1.4 2.1 2.4.5-.9 2.3 1.6 1.8-1.9 1.6.3 2.5-2.4.7-1.1 2.2-2.2-1.1-2.2 1.1-1.1-2.2-2.4-.7.3-2.5-1.9-1.6 1.6-1.8-.9-2.3 2.4-.5L10 3.5zm0 4.2a2.3 2.3 0 1 0 0 4.6 2.3 2.3 0 0 0 0-4.6z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg></span>
                <span>Automation</span>
              </a>
            </div>
          </div>
          @endif

          <p class="panel-nav-group-title">Delivery</p>
          <a class="panel-nav-link @if(request()->routeIs('admin.projects.index')) is-active @endif" href="{{ route('admin.projects.index') }}" title="Projects">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h7l2 2h3a2 2 0 0 1 2 2v2H4V5zm0 5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9zm4 3h8m-8 3h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Projects</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.media-delivery.*') && !request()->routeIs('admin.media-delivery.watermark.*')) is-active @endif" href="{{ route('admin.media-delivery.index') }}" title="Media Delivery">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3H4V6zm0 5h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7zm3 2h10m-6 3h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Media Delivery</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.media-delivery.watermark.*')) is-active @endif" href="{{ route('admin.media-delivery.watermark.index') }}" title="Watermark Settings">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Watermark Settings</span>
          </a>

          <p class="panel-nav-group-title">Accounts</p>
          <a class="panel-nav-link @if(request()->routeIs('admin.clients.*')) is-active @endif" href="{{ route('admin.clients.index') }}" title="Clients">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.57 2.99-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11zM8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V20h7v-3.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Clients</span>
          </a>
          @if($canManageUsers)
          <a class="panel-nav-link @if(request()->routeIs('admin.users.*')) is-active @endif" href="{{ route('admin.users.index') }}" title="User Accounts">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm-7 8a7 7 0 0 1 14 0H5zm12.5-9.5h4m-2-2v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="panel-nav-text">User Accounts</span>
          </a>
          @endif
          @else
          <p class="panel-nav-group-title">Overview</p>
          <a class="panel-nav-link @if(request()->routeIs('user.dashboard')) is-active @endif" href="{{ route('user.dashboard') }}" title="My Dashboard">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9zm0 4a2.5 2.5 0 1 1-2.5 2.5A2.5 2.5 0 0 1 12 7zm0 11a6 6 0 0 1-4.85-2.45 4.8 4.8 0 0 1 9.7 0A6 6 0 0 1 12 18z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">My Dashboard</span>
          </a>
          @endif
        @endauth
        <p class="panel-nav-group-title">Website</p>
        <a class="panel-nav-link" href="{{ route('home') }}" title="Public Website">
          <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l9 7h-3v11h-5v-6H11v6H6V9H3l9-7z" fill="currentColor"/></svg></span>
          <span class="panel-nav-text">Public Website</span>
        </a>
      </nav>

      @auth
      <div class="panel-sidebar-foot">
        <form action="{{ route('logout') }}" method="post" class="panel-sidebar-logout-form">
          @csrf
          <button class="panel-btn panel-btn-primary panel-sidebar-logout" type="submit" title="Log out">
            <span class="panel-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 17l5-5-5-5M21 12H9M12 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span>Log out</span>
          </button>
        </form>
      </div>
      @endauth
    </aside>
    <button class="panel-sidebar-overlay" type="button" aria-label="Close sidebar" data-panel-overlay></button>

    <div class="panel-main">
      <main class="panel-shell">
        <header class="panel-topbar">
          <div class="panel-topbar-left">
            <button class="panel-mobile-toggle" type="button" aria-label="Toggle sidebar" aria-controls="panel-sidebar" aria-expanded="false" data-panel-toggle>
              <span></span><span></span><span></span>
            </button>
            <div>
              <h1 class="panel-title">{{ $heading ?? 'Dashboard' }}</h1>
              @if (!empty($subheading))
              <p class="panel-sub">{{ $subheading }}</p>
              @endif
            </div>
          </div>

          @auth
          <div class="panel-actions">
            <div class="panel-notify" data-panel-notify data-feed-url="{{ route('notifications.feed') }}" data-read-all-url="{{ route('notifications.read-all-ajax') }}" data-read-url-template="{{ url('/notifications/__ID__/read-ajax') }}" data-csrf="{{ csrf_token() }}">
              <button class="panel-notify-btn" type="button" aria-expanded="false" data-panel-notify-toggle title="Notifications">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a5 5 0 0 0-5 5v2.3c0 .8-.3 1.57-.84 2.14L5 13.73V15h14v-1.27l-1.16-1.3A3 3 0 0 1 17 10.3V8a5 5 0 0 0-5-5zm0 18a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 21z" fill="currentColor"/></svg>
                @if(($panelUnreadNotifications ?? 0) > 0)
                <span class="panel-notify-count">{{ $panelUnreadNotifications }}</span>
                @endif
              </button>
              <div class="panel-notify-menu" data-panel-notify-menu hidden>
                <div class="panel-notify-head">
                  <strong>Notifications</strong>
                  <button class="panel-link" type="button" data-notify-mark-all @if(($panelUnreadNotifications ?? 0) === 0) hidden @endif>Mark all read</button>
                </div>
                <div class="panel-notify-filters">
                  <button class="panel-notify-filter is-active" type="button" data-notify-filter="all">All</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="quotes">Quotes</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="invoices">Invoices</button>
                  <button class="panel-notify-filter" type="button" data-notify-filter="messages">Messages</button>
                </div>
                <div class="panel-notify-list">
                  @php
                    $notifyCategoryMap = [
                      'new_quote_submission' => 'quotes',
                      'quote_status_updated' => 'quotes',
                      'quote_revision_requested' => 'quotes',
                      'invoice_created' => 'invoices',
                      'invoice_status_updated' => 'invoices',
                      'new_admin_message' => 'messages',
                      'new_service_request' => 'messages',
                      'service_request_status_updated' => 'messages',
                      'project_status_updated' => 'messages',
                    ];
                  @endphp
                  @forelse(($panelNotifications ?? collect()) as $notification)
                  <div class="panel-notify-item {{ $notification->read_at ? '' : 'is-unread' }}" data-notify-category="{{ $notifyCategoryMap[$notification->type] ?? 'other' }}">
                    <div class="panel-notify-copy">
                      <p class="panel-notify-title">{{ $notification->title }}</p>
                      @if($notification->body)
                      <p class="panel-notify-body">{{ $notification->body }}</p>
                      @endif
                      <p class="panel-notify-time">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="panel-notify-actions">
                      @if($notification->action_url)
                      <a class="panel-link" href="{{ $notification->action_url }}">Open</a>
                      @endif
                    </div>
                  </div>
                  @empty
                  <p class="panel-muted">No notifications yet.</p>
                  @endforelse
                  <p class="panel-muted" data-notify-empty hidden>No notifications in this category.</p>
                </div>
              </div>
            </div>
            <form action="{{ route('logout') }}" method="post">
              @csrf
              <button class="panel-btn panel-btn-primary" type="submit">Log out</button>
            </form>
          </div>
          @endauth
        </header>

        @if (session('status'))
        <section class="panel-card"><span class="panel-badge">{{ session('status') }}</span></section>
        @endif

        @if ($errors->any())
        <section class="panel-card"><span class="panel-badge panel-badge-danger">{{ $errors->first() }}</span></section>
        @endif

        @yield('content')
      </main>
    </div>
  </div>

  <script>
    (function () {
      const app = document.getElementById('panel-app');
      const mobileToggle = document.querySelector('[data-panel-toggle]');
      const collapseToggle = document.querySelector('[data-panel-collapse]');
      const sidebarOverlay = document.querySelector('[data-panel-overlay]');
      if (!app) return;

      const storageKey = 'maccento_panel_sidebar_collapsed';
      const media = window.matchMedia('(max-width: 1100px)');

      const applyStoredState = function () {
        if (media.matches) return;
        const collapsed = localStorage.getItem(storageKey) === '1';
        app.classList.toggle('sidebar-collapsed', collapsed);
      };

      applyStoredState();

      if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
          const open = app.classList.toggle('sidebar-open');
          mobileToggle.setAttribute('aria-expanded', String(open));
        });
      }

      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
          app.classList.remove('sidebar-open');
          if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', 'false');
          }
        });
      }

      document.querySelectorAll('.panel-sidebar .panel-nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
          if (!media.matches) return;
          app.classList.remove('sidebar-open');
          if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', 'false');
          }
        });
      });

      if (collapseToggle) {
        collapseToggle.addEventListener('click', function () {
          if (media.matches) return;
          const collapsed = app.classList.toggle('sidebar-collapsed');
          localStorage.setItem(storageKey, collapsed ? '1' : '0');
        });
      }

      media.addEventListener('change', function () {
        if (media.matches) {
          app.classList.remove('sidebar-collapsed');
          return;
        }
        app.classList.remove('sidebar-open');
        if (mobileToggle) {
          mobileToggle.setAttribute('aria-expanded', 'false');
        }
        applyStoredState();
      });

      const nav = document.querySelector('.panel-nav');
      if (nav) {
        const groupTitles = Array.from(nav.querySelectorAll(':scope > .panel-nav-group-title'));
        const slugify = function (value) {
          return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'group';
        };

        groupTitles.forEach(function (titleEl, index) {
          const titleText = String(titleEl.textContent || '').trim();
          const groupId = slugify(titleText) + '-' + String(index + 1);
          const section = document.createElement('section');
          section.className = 'panel-nav-group';
          section.setAttribute('data-nav-section', groupId);

          const toggle = document.createElement('button');
          toggle.type = 'button';
          toggle.className = 'panel-nav-group-toggle';
          toggle.setAttribute('data-nav-section-toggle', groupId);
          toggle.setAttribute('aria-expanded', 'true');
          toggle.innerHTML = '' +
            '<span class="panel-nav-group-toggle-text">' + titleText + '</span>' +
            '<span class="panel-nav-group-toggle-arrow" aria-hidden="true">' +
              '<svg viewBox="0 0 20 20"><path d="M6 8l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '</span>';

          const body = document.createElement('div');
          body.className = 'panel-nav-group-body';
          body.setAttribute('data-nav-section-body', groupId);

          nav.insertBefore(section, titleEl);
          titleEl.remove();

          section.appendChild(toggle);
          section.appendChild(body);

          let cursor = section.nextElementSibling;
          while (cursor && !cursor.classList.contains('panel-nav-group-title')) {
            const next = cursor.nextElementSibling;
            body.appendChild(cursor);
            cursor = next;
          }

          const storageKey = 'maccento_panel_nav_group_collapsed_' + groupId;
          const hasActive = body.querySelector('.is-active') !== null;
          const stored = localStorage.getItem(storageKey);
          const collapsed = stored === null ? !hasActive : stored === '1';

          const applyGroupState = function (isCollapsed) {
            section.classList.toggle('is-collapsed', isCollapsed);
            toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
          };

          applyGroupState(collapsed);

          toggle.addEventListener('click', function () {
            const nextState = !section.classList.contains('is-collapsed');
            applyGroupState(nextState);
            localStorage.setItem(storageKey, nextState ? '1' : '0');
          });
        });
      }

      const subnavGroups = Array.from(document.querySelectorAll('[data-subnav-group]'));
      subnavGroups.forEach(function (group) {
        const key = String(group.getAttribute('data-subnav-group') || '').trim();
        if (key === '') return;

        const toggle = group.querySelector('[data-subnav-toggle="' + key + '"]');
        const submenu = group.querySelector('[data-subnav="' + key + '"]');
        if (!toggle || !submenu) return;

        const stateKey = 'maccento_panel_subnav_collapsed_' + key;
        const apply = function (collapsed) {
          group.classList.toggle('is-collapsed', collapsed);
          toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        };

        const stored = localStorage.getItem(stateKey);
        const defaultCollapsed = !group.classList.contains('is-active');
        apply(stored === null ? defaultCollapsed : stored === '1');

        toggle.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          const collapsed = !group.classList.contains('is-collapsed');
          apply(collapsed);
          localStorage.setItem(stateKey, collapsed ? '1' : '0');
        });
      });

      const notifyWrap = document.querySelector('[data-panel-notify]');
      const notifyToggle = document.querySelector('[data-panel-notify-toggle]');
      const notifyMenu = document.querySelector('[data-panel-notify-menu]');
      if (notifyWrap && notifyToggle && notifyMenu) {
        const filterButtons = notifyMenu.querySelectorAll('[data-notify-filter]');
        const filteredEmpty = notifyMenu.querySelector('[data-notify-empty]');
        const listEl = notifyMenu.querySelector('.panel-notify-list');
        const markAllBtn = notifyMenu.querySelector('[data-notify-mark-all]');
        const feedUrl = notifyWrap.getAttribute('data-feed-url') || '';
        const readAllUrl = notifyWrap.getAttribute('data-read-all-url') || '';
        const readUrlTemplate = notifyWrap.getAttribute('data-read-url-template') || '';
        const csrfToken = notifyWrap.getAttribute('data-csrf') || '';
        const categoryMap = {
          new_quote_submission: 'quotes',
          quote_status_updated: 'quotes',
          quote_revision_requested: 'quotes',
          invoice_created: 'invoices',
          invoice_status_updated: 'invoices',
          new_admin_message: 'messages',
          new_service_request: 'messages',
          service_request_status_updated: 'messages',
          project_status_updated: 'messages'
        };

        let currentFilter = 'all';
        let notifications = [];
        let isFetching = false;

        const escapeHtml = function (value) {
          return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        };

        const updateUnreadBadge = function (count) {
          let badge = notifyToggle.querySelector('.panel-notify-count');
          if (count > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'panel-notify-count';
              notifyToggle.appendChild(badge);
            }
            badge.textContent = String(count);
          } else if (badge) {
            badge.remove();
          }

          if (markAllBtn) {
            markAllBtn.hidden = count <= 0;
          }
        };

        const buildNotificationItem = function (item) {
          const isUnread = Boolean(item && item.is_unread);
          const category = categoryMap[String(item.type || '')] || 'other';
          const actionUrl = String(item.action_url || '');
          const title = escapeHtml(item.title || 'Notification');
          const body = escapeHtml(item.body || '');
          const time = escapeHtml(item.created_human || '');

          const wrapper = document.createElement('div');
          wrapper.className = 'panel-notify-item' + (isUnread ? ' is-unread' : '');
          wrapper.setAttribute('data-notify-category', category);
          wrapper.setAttribute('data-notify-id', String(item.id || ''));

          let actionsHtml = '';
          if (actionUrl !== '') {
            actionsHtml = '<div class="panel-notify-actions"><a class="panel-link" href="' + escapeHtml(actionUrl) + '" data-notify-open="1">Open</a></div>';
          }

          wrapper.innerHTML = '' +
            '<div class="panel-notify-copy">' +
              '<p class="panel-notify-title">' + title + '</p>' +
              (body !== '' ? '<p class="panel-notify-body">' + body + '</p>' : '') +
              (time !== '' ? '<p class="panel-notify-time">' + time + '</p>' : '') +
            '</div>' +
            actionsHtml;

          return wrapper;
        };

        const renderNotifications = function () {
          if (!listEl) return;

          const staticEmpty = listEl.querySelector('[data-notify-empty]');
          listEl.querySelectorAll('[data-notify-category], .panel-muted:not([data-notify-empty])').forEach(function (el) {
            el.remove();
          });

          if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'panel-muted';
            empty.textContent = 'No notifications yet.';
            listEl.insertBefore(empty, staticEmpty || null);
          } else {
            notifications.forEach(function (item) {
              const row = buildNotificationItem(item);
              listEl.insertBefore(row, staticEmpty || null);
            });
          }

          applyNotifyFilter(currentFilter);
        };

        const applyNotifyFilter = function (filterKey) {
          const items = notifyMenu.querySelectorAll('[data-notify-category]');
          let visible = 0;
          items.forEach(function (item) {
            const category = item.getAttribute('data-notify-category') || 'other';
            const show = filterKey === 'all' || category === filterKey;
            item.classList.toggle('is-hidden', !show);
            if (show) visible += 1;
          });
          if (filteredEmpty) {
            filteredEmpty.classList.toggle('is-hidden', visible !== 0);
          }
          filterButtons.forEach(function (button) {
            const active = button.getAttribute('data-notify-filter') === filterKey;
            button.classList.toggle('is-active', active);
          });
          currentFilter = filterKey;
        };

        const fetchFeed = function () {
          if (!feedUrl || isFetching) {
            return;
          }

          isFetching = true;
          fetch(feedUrl, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Feed request failed');
              }
              return response.json();
            })
            .then(function (data) {
              notifications = Array.isArray(data && data.notifications) ? data.notifications : [];
              renderNotifications();
              updateUnreadBadge(Number(data && data.unread_count ? data.unread_count : 0));
            })
            .catch(function () {
            })
            .finally(function () {
              isFetching = false;
            });
        };

        const markRead = function (notificationId, onDone) {
          const id = String(notificationId || '').trim();
          if (id === '' || !readUrlTemplate || !csrfToken) {
            if (typeof onDone === 'function') onDone();
            return;
          }

          const url = readUrlTemplate.replace('__ID__', encodeURIComponent(id));
          fetch(url, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({})
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Read request failed');
              }
              return response.json();
            })
            .then(function (data) {
              const index = notifications.findIndex(function (row) { return String(row.id) === id; });
              if (index >= 0) {
                notifications[index].is_unread = false;
              }
              renderNotifications();
              updateUnreadBadge(Number(data && data.unread_count ? data.unread_count : 0));
            })
            .catch(function () {
            })
            .finally(function () {
              if (typeof onDone === 'function') onDone();
            });
        };

        if (markAllBtn) {
          markAllBtn.addEventListener('click', function () {
            if (!readAllUrl || !csrfToken) {
              return;
            }

            fetch(readAllUrl, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin',
              body: JSON.stringify({})
            })
              .then(function (response) {
                if (!response.ok) {
                  throw new Error('Read all request failed');
                }
                return response.json();
              })
              .then(function () {
                notifications = notifications.map(function (row) {
                  row.is_unread = false;
                  return row;
                });
                renderNotifications();
                updateUnreadBadge(0);
              })
              .catch(function () {
              });
          });
        }

        notifyMenu.addEventListener('click', function (event) {
          const openLink = event.target.closest('[data-notify-open]');
          if (openLink) {
            const row = openLink.closest('[data-notify-id]');
            const notifyId = row ? row.getAttribute('data-notify-id') : '';
            if (notifyId) {
              event.preventDefault();
              const href = openLink.getAttribute('href') || '';
              markRead(notifyId, function () {
                if (href !== '') {
                  window.location.href = href;
                }
              });
            }
            return;
          }

          const row = event.target.closest('[data-notify-id]');
          if (!row) {
            return;
          }

          if (event.target.closest('a,button,form')) {
            return;
          }

          const notifyId = row.getAttribute('data-notify-id') || '';
          if (notifyId && row.classList.contains('is-unread')) {
            markRead(notifyId);
          }
        });

        filterButtons.forEach(function (button) {
          button.addEventListener('click', function () {
            applyNotifyFilter(button.getAttribute('data-notify-filter') || 'all');
          });
        });

        notifyToggle.addEventListener('click', function () {
          const open = notifyMenu.hidden;
          notifyMenu.hidden = !open;
          notifyToggle.setAttribute('aria-expanded', String(open));
          if (open) {
            fetchFeed();
            applyNotifyFilter(currentFilter || 'all');
          }
        });
        document.addEventListener('click', function (event) {
          if (!notifyWrap.contains(event.target)) {
            notifyMenu.hidden = true;
            notifyToggle.setAttribute('aria-expanded', 'false');
          }
        });

        fetchFeed();
        window.setInterval(fetchFeed, 15000);
      }
    })();
  </script>
</body>
</html>
