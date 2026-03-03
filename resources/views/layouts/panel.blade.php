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
          <a class="panel-nav-link @if(request()->routeIs('admin.form-submissions*')) is-active @endif" href="{{ route('admin.form-submissions') }}" title="Website Submissions">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14a2 2 0 0 1 2 2v14l-4-3-4 3-4-3-4 3V5a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
            <span class="panel-nav-text">Website Submissions</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.*') && !request()->routeIs('admin.leads.ai.*') && !request()->routeIs('admin.leads.packages.*')) is-active @endif" href="{{ route('admin.leads.index') }}" title="Lead Pipeline">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Lead Pipeline</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.ai.*')) is-active @endif" href="{{ route('admin.leads.ai.index') }}" title="Leads from AI Assistant">
            <span class="panel-nav-icon"><img src="{{ asset('assets/media/icon/ai_icon.png') }}" alt="" aria-hidden="true"></span>
            <span class="panel-nav-text">Leads from AI Assistant</span>
          </a>
          <a class="panel-nav-link @if(request()->routeIs('admin.leads.packages.*')) is-active @endif" href="{{ route('admin.leads.packages.index') }}" title="Leads from Packages">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7.5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v4a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-4H5a2 2 0 0 1-2-2v-3zm5 5v4h8v-4H8zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9z" fill="currentColor"/></svg></span>
            <span class="panel-nav-text">Leads from Packages</span>
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
          @endif

          <p class="panel-nav-group-title">Delivery</p>
          <a class="panel-nav-link @if(request()->routeIs('admin.projects.index')) is-active @endif" href="{{ route('admin.projects.index') }}" title="Projects">
            <span class="panel-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h7l2 2h3a2 2 0 0 1 2 2v2H4V5zm0 5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9zm4 3h8m-8 3h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="panel-nav-text">Projects</span>
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
        <span class="panel-badge panel-role-badge">{{ $accessLabel }}</span>
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
            <div class="panel-notify" data-panel-notify>
              <button class="panel-notify-btn" type="button" aria-expanded="false" data-panel-notify-toggle title="Notifications">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a5 5 0 0 0-5 5v2.3c0 .8-.3 1.57-.84 2.14L5 13.73V15h14v-1.27l-1.16-1.3A3 3 0 0 1 17 10.3V8a5 5 0 0 0-5-5zm0 18a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 21z" fill="currentColor"/></svg>
                @if(($panelUnreadNotifications ?? 0) > 0)
                <span class="panel-notify-count">{{ $panelUnreadNotifications }}</span>
                @endif
              </button>
              <div class="panel-notify-menu" data-panel-notify-menu hidden>
                <div class="panel-notify-head">
                  <strong>Notifications</strong>
                  @if(($panelUnreadNotifications ?? 0) > 0)
                  <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="panel-link" type="submit">Mark all read</button>
                  </form>
                  @endif
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
                      @if(!$notification->read_at)
                      <form method="post" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button class="panel-link" type="submit">Read</button>
                      </form>
                      @endif
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
            <span class="panel-badge panel-role-badge">{{ $accessLabel }}</span>
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

      const notifyWrap = document.querySelector('[data-panel-notify]');
      const notifyToggle = document.querySelector('[data-panel-notify-toggle]');
      const notifyMenu = document.querySelector('[data-panel-notify-menu]');
      if (notifyWrap && notifyToggle && notifyMenu) {
        const filterButtons = notifyMenu.querySelectorAll('[data-notify-filter]');
        const items = notifyMenu.querySelectorAll('[data-notify-category]');
        const filteredEmpty = notifyMenu.querySelector('[data-notify-empty]');
        const applyNotifyFilter = function (filterKey) {
          let visible = 0;
          items.forEach(function (item) {
            const category = item.getAttribute('data-notify-category') || 'other';
            const show = filterKey === 'all' || category === filterKey;
            item.hidden = !show;
            if (show) visible += 1;
          });
          if (filteredEmpty) {
            filteredEmpty.hidden = visible !== 0;
          }
          filterButtons.forEach(function (button) {
            const active = button.getAttribute('data-notify-filter') === filterKey;
            button.classList.toggle('is-active', active);
          });
        };

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
            applyNotifyFilter('all');
          }
        });
        document.addEventListener('click', function (event) {
          if (!notifyWrap.contains(event.target)) {
            notifyMenu.hidden = true;
            notifyToggle.setAttribute('aria-expanded', 'false');
          }
        });
      }
    })();
  </script>
</body>
</html>
