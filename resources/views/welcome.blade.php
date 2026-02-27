<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maccento | Real Estate Media</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/media/favicon.ico') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
</head>
<body>
  @php
    $page = $page ?? 'home';
    $isHome = $page === 'home';
    $showServices = in_array($page, ['home', 'services'], true);
    $showPortfolio = in_array($page, ['home', 'portfolio'], true);
    $showPlan = in_array($page, ['home', 'plan'], true);
  @endphp
  <header class="header">
    <div class="container nav">
      <a href="{{ route('home') }}" aria-label="Maccento">
        <img loading="eager" fetchpriority="high" decoding="async" class="logo" src="{{ asset('assets/media/logo-footer.png') }}" alt="Maccento logo">
      </a>
      <nav class="nav-links">
        <a href="{{ route('about') }}">ABOUT US</a>
        <a href="{{ route('services') }}">OUR SERVICES</a>
        <a href="{{ route('portfolio') }}">PORTFOLIO</a>
        <a href="{{ route('plan') }}">OUR PLAN</a>
      </nav>
      <div class="nav-actions">
        <div class="nav-cta">
          <a class="btn header-login-btn" href="{{ route('login') }}">Client Login</a>
        </div>
        <div class="lang-toggle" role="group" aria-label="Language toggle">
          <button class="lang-btn active" type="button" data-lang="en" aria-pressed="true">EN</button>
          <button class="lang-btn" type="button" data-lang="fr" aria-pressed="false">FR</button>
        </div>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>
    <nav id="mobile-menu" class="mobile-menu" aria-hidden="true">
      <div class="container mobile-menu-inner">
        <a class="mobile-menu-login" href="{{ route('login') }}">CLIENT LOGIN</a>
        <a class="mobile-menu-link" href="{{ route('about') }}">ABOUT US</a>
        <a class="mobile-menu-link" href="{{ route('services') }}">OUR SERVICES</a>
        <a class="mobile-menu-link" href="{{ route('portfolio') }}">PORTFOLIO</a>
        <a class="mobile-menu-link" href="{{ route('plan') }}">OUR PLAN</a>
        <div class="mobile-menu-lang" role="group" aria-label="Language toggle">
          <button class="lang-btn active" type="button" data-lang="en" aria-pressed="true">EN</button>
          <button class="lang-btn" type="button" data-lang="fr" aria-pressed="false">FR</button>
        </div>
      </div>
    </nav>
  </header>

  <main id="top">
    @if(!$isHome && $page !== 'services' && $page !== 'portfolio' && $page !== 'plan' && $page !== 'about')
    <section class="section page-intro">
      <div class="container">
        <h1 class="section-title page-intro-title">
          @if($page === 'services') Our Services @endif
          @if($page === 'portfolio') Portfolio @endif
          @if($page === 'plan') Our Plan @endif
        </h1>
      </div>
    </section>
    @endif
    @if($page === 'about')
    <section class="section about-page">
      <div class="container about-grid">
        <div class="about-copy">
          <h1 class="section-title about-title">Pioneers of Real Estate Listing Intelligence</h1>
          <p class="section-sub about-sub">We build marketing systems that help brokers launch faster, present properties better, and keep quality consistent across every listing. From capture to delivery, every step is designed for speed, clarity, and premium output.</p>
          <div class="about-cta">
            <a class="btn btn-primary" href="#contact">Book Now</a>
            <a class="btn about-ghost-btn" href="{{ route('plan') }}">See our plans</a>
          </div>
        </div>
        <div class="about-collage" aria-label="About Maccento gallery">
          <figure class="about-card card-a"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos1.webp') }}" alt="Team discussing listing assets"></figure>
          <figure class="about-card card-b"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos2.webp') }}" alt="Client strategy meeting"></figure>
          <figure class="about-card card-c"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos3.webp') }}" alt="Production workflow"></figure>
          <figure class="about-card card-d"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos4.webp') }}" alt="Creative team collaboration"></figure>
          <figure class="about-card card-e"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos5.webp') }}" alt="Planning session with brokers"></figure>
        </div>
      </div>
    </section>

    <section class="section why-maccento">
      <div class="container why-stack">
        <div class="why-head">
          <h2 class="section-title">A Smarter Approach to Real Estate Media</h2>
          <p class="section-sub">Elevated visuals. Seamless execution. Consistent results.</p>
        </div>
        <div class="why-orbit">
          <div class="why-col">
            <article class="why-item">
              <div class="why-item-icon">
                <img loading="lazy" decoding="async" src="{{ asset('assets/media/icon/Cinematic Property Films.webp') }}" alt="Tailored Creative Approach icon">
              </div>
              <h4>Tailored Creative Approach</h4>
              <p>Every project is shaped around your vision, your timeline, and the unique character of each space - never a one-size-fits-all process.</p>
            </article>
            <article class="why-item">
              <div class="why-item-icon">
                <img loading="lazy" decoding="async" src="{{ asset('assets/media/icon/Express Video Walkthroughs.webp') }}" alt="Fast, Reliable Turnaround icon">
              </div>
              <h4>Fast, Reliable Turnaround</h4>
              <p>Streamlined workflows allow us to deliver polished visuals quickly without compromising quality or consistency.</p>
            </article>
          </div>
          <div class="why-center-media">
            <video class="lazy-video" autoplay muted loop playsinline preload="none">
              <source data-src="{{ asset('assets/media/webm/Videos3.webm') }}" type="video/webm">
            </video>
          </div>
          <div class="why-col">
            <article class="why-item">
              <div class="why-item-icon">
                <img loading="lazy" decoding="async" src="{{ asset('assets/media/icon/Drone Photography & Video.webp') }}" alt="Certified and Fully Insured Drone Operations icon">
              </div>
              <h4>Certified &amp; Fully Insured Drone Operations</h4>
              <p>Transport Canada-licensed pilots ensure safe, compliant, and cinematic aerial perspectives on every shoot.</p>
            </article>
            <article class="why-item">
              <div class="why-item-icon">
                <img loading="lazy" decoding="async" src="{{ asset('assets/media/icon/Social Media Content for Brokers.webp') }}" alt="Flexible Scheduling icon">
              </div>
              <h4>Flexible Scheduling</h4>
              <p>Early mornings, evenings, and tight timelines - we adapt to your schedule to keep your projects moving.</p>
            </article>
          </div>
        </div>
      </div>
    </section>
    @endif
    @if($isHome)
    <section class="hero">
      <video autoplay muted loop playsinline preload="auto">
        <source src="{{ asset('assets/media/webm/Videos4.webm') }}" type="video/webm">
      </video>
      <div class="container">
        <h1 class="home-hero-title">The Real Estate Media Partner For Brokers</h1>
        <p class="hero-sub">On-site media across Greater Montreal. Remote editing for brokers anywhere.</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="{{ route('login') }}">Book Now</a>
          <a class="btn btn-ghost" href="{{ route('plan') }}">View Packages</a>
        </div>
      </div>
    </section>
    @endif

    @if($isHome)
    <section id="why" class="section offerings">
      <div class="container">
        <h2 class="section-title">Our Offerings</h2>
        <p class="section-sub">Marketing-ready media across every step of the listing journey.</p>
        <div class="offer-grid">
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Professional Photography (HDR).webp') }}" alt="Professional Photography">
            <h4>Professional Photography (HDR)</h4>
            <p>High-end interior & exterior photography for residential and commercial listings.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Cinematic Property Films.webp') }}" alt="Cinematic Property Films">
            <h4>Cinematic Property Films</h4>
            <p>High-production videos with storytelling, music, gimbal movement, and drone integration.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Express Video Walkthroughs.webp') }}" alt="Express Video Walkthroughs">
            <h4>Express Video Walkthroughs</h4>
            <p>Clean, fast, MLS-friendly walkthroughs designed for speed, volume, and social use.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Drone Photography & Video.webp') }}" alt="Drone Photography">
            <h4>Drone Photography & Video</h4>
            <p>Aerial imagery and video to showcase location, scale, surroundings, and access points.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Virtual Staging.webp') }}" alt="Virtual Staging">
            <h4>Virtual Staging</h4>
            <p>Digitally staged interiors to help buyers visualize scale, layout, and lifestyle.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Day-to-Dusk.webp') }}" alt="Day-to-Dusk">
            <h4>Day-to-Dusk</h4>
            <p>Twilight-style exterior imagery to enhance curb appeal and listing presence.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/3D Tours.webp') }}" alt="3D Tours">
            <h4>3D Tours (Matterport) - coming soon</h4>
            <p>Immersive virtual tours allowing buyers and tenants to explore spaces remotely.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Floor Plans.webp') }}" alt="Floor Plans">
            <h4>Floor Plans (coming soon)</h4>
            <p>Accurate 2D & 3D floor plans for listings, marketing materials, and commercial use.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Social Media Content for Brokers.webp') }}" alt="Social Media Content">
            <h4>Social Media Content for Brokers</h4>
            <p>Short-form videos, reels, and visuals designed for Instagram, TikTok, and LinkedIn.</p>
          </div>
          <div class="offer">
            <img loading="lazy" decoding="async" class="offer-icon" src="{{ asset('assets/media/icon/Photo Retouching & Media Enhancement.webp') }}" alt="Photo Retouching">
            <h4>Photo Retouching & Media Enhancement</h4>
            <p>Professional post-production including color correction, object removal, and polish.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="service-examples" class="section examples-showcase">
      <div class="container">
        <div class="examples-head">
          <h2 class="section-title">Service Examples</h2>
          <p class="section-sub">See exactly what each service delivers with real visual outputs.</p>
        </div>
        <div class="examples-grid">
          <article class="example-card">
            <div class="example-media video-card">
              <span class="example-label">Photo-to-Video</span>
              <video class="lazy-video" autoplay muted loop playsinline preload="none">
                <source data-src="{{ asset('assets/media/webm/Videos2.webm') }}" type="video/webm">
              </video>
            </div>
            <div class="example-copy">
              <h3>Photo-to-video content</h3>
              <ul>
                <li>Turns still photos into social-ready reels</li>
                <li>Built for listing launches and ads</li>
                <li>Fast delivery with clean motion pacing</li>
              </ul>
            </div>
          </article>

          <article class="example-card">
            <div class="example-media compare-media" data-compare>
              <img class="compare-before" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Virtual staging before.webp') }}" alt="Virtual staging before example">
              <div class="compare-after-wrap">
                <img class="compare-after" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Virtual staging after.webp') }}" alt="Virtual staging after example">
              </div>
              <span class="split-tag compare-tag before">Before</span>
              <span class="split-tag compare-tag after">After</span>
              <button class="compare-handle" type="button" aria-label="Drag to compare before and after" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                <span class="compare-handle-line" aria-hidden="true"></span>
                <span class="compare-handle-knob" aria-hidden="true">&#8596;</span>
              </button>
            </div>
            <div class="example-copy">
              <h3>Virtual staging</h3>
              <ul>
                <li>Transforms empty spaces into furnished rooms</li>
                <li>Helps buyers visualize layout and lifestyle</li>
                <li>Aligned with listing style and target audience</li>
              </ul>
            </div>
          </article>

          <article class="example-card">
            <div class="example-media compare-media" data-compare>
              <img class="compare-before" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Day-to-dusk edits before.webp') }}" alt="Day to dusk before example">
              <div class="compare-after-wrap">
                <img class="compare-after" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Day-to-dusk edits after.webp') }}" alt="Day to dusk after example">
              </div>
              <span class="split-tag compare-tag before">Before</span>
              <span class="split-tag compare-tag after">After</span>
              <button class="compare-handle" type="button" aria-label="Drag to compare before and after" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                <span class="compare-handle-line" aria-hidden="true"></span>
                <span class="compare-handle-knob" aria-hidden="true">&#8596;</span>
              </button>
            </div>
            <div class="example-copy">
              <h3>Day-to-dusk edits</h3>
              <ul>
                <li>Converts daytime exteriors into twilight mood</li>
                <li>Enhances curb appeal and premium perception</li>
                <li>Creates stronger first-impression visuals</li>
              </ul>
            </div>
          </article>

          <article class="example-card">
            <div class="example-media compare-media" data-compare>
              <img class="compare-before" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Image editing_object removal before.webp') }}" alt="Image editing before example">
              <div class="compare-after-wrap">
                <img class="compare-after" loading="lazy" decoding="async" src="{{ asset('assets/media/Service Examples/Image editing_object removal after.webp') }}" alt="Image editing after example">
              </div>
              <span class="split-tag compare-tag before">Before</span>
              <span class="split-tag compare-tag after">After</span>
              <button class="compare-handle" type="button" aria-label="Drag to compare before and after" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                <span class="compare-handle-line" aria-hidden="true"></span>
                <span class="compare-handle-knob" aria-hidden="true">&#8596;</span>
              </button>
            </div>
            <div class="example-copy">
              <h3>Image editing / object removal</h3>
              <ul>
                <li>Removes visual distractions and clutter</li>
                <li>Balances lighting, color, and composition</li>
                <li>Keeps final images polished and MLS-ready</li>
              </ul>
            </div>
          </article>
        </div>
      </div>
    </section>
    @endif

    @if($page === 'services')
    <section class="section services-page">
      <div class="container">
        <div class="services-heading-wrap">
          <h1 class="section-title services-page-heading">Marketing-Ready Media Across Every Step of the Listing Journey.</h1>
        </div>
        <div class="services-grid">
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos1.webp') }}" alt="Professional Photography (HDR)">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Professional Photography (HDR)</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">High-end interior & exterior photography for residential and commercial listings.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos2.webp') }}" alt="Cinematic Property Films">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Cinematic Property Films</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">High-production videos with storytelling, music, gimbal movement, and drone integration.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos3.webp') }}" alt="Express Video Walkthroughs">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Express Video Walkthroughs</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Clean, fast, MLS-friendly walkthroughs designed for speed, volume, and social use.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone1.webp') }}" alt="Drone Photography & Video">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Drone Photography &amp; Video</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Aerial imagery and video to showcase location, scale, surroundings, and access points.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos4.webp') }}" alt="Virtual Staging">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Virtual Staging</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Digitally staged interiors to help buyers visualize scale, layout, and lifestyle.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos5.webp') }}" alt="Day-to-Dusk">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Day-to-Dusk</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Twilight-style exterior imagery to enhance curb appeal and listing presence.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos6.webp') }}" alt="3D Tours (Matterport) - coming soon">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>3D Tours (Matterport) - coming soon</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Immersive virtual tours allowing buyers and tenants to explore spaces remotely.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone2.webp') }}" alt="Floor Plans (coming soon)">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Floor Plans (coming soon)</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Accurate 2D &amp; 3D floor plans for listings, marketing materials, and commercial use.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone3.webp') }}" alt="Social Media Content for Brokers">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Social Media Content for Brokers</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Short-form videos, reels, and visuals designed for Instagram, TikTok, and LinkedIn.</p>
            </div>
          </article>
          <article class="service-card reveal-on-scroll">
            <figure class="service-media">
              <img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone4.webp') }}" alt="Photo Retouching & Media Enhancement">
            </figure>
            <div class="service-content">
              <div class="service-head">
                <h3>Photo Retouching &amp; Media Enhancement</h3>
                <button class="service-toggle" type="button" aria-expanded="false" aria-label="Toggle service details"><span class="service-toggle-icon" aria-hidden="true">▾</span></button>
              </div>
              <p class="service-reveal-copy">Professional post-production including color correction, object removal, and polish.</p>
            </div>
          </article>
        </div>
      </div>
    </section>
    @endif

    @if($showPortfolio)
    <section id="portfolio" class="section {{ $isHome ? 'portfolio-home' : '' }}">
      <div class="container">
        <div class="portfolio-head">
          @if($isHome)
          <h2 class="section-title portfolio-page-heading home-portfolio-heading">Residential and Commercial Portfolio</h2>
          <p class="section-sub">Browse recent work by property type.</p>
          @else
          <h2 class="section-title portfolio-page-heading">Browse Recent Work by Property Type</h2>
          @endif
        </div>
        <div class="portfolio-tabs" data-portfolio-tabs>
          <button class="portfolio-btn active" data-filter="residential">Residential</button>
          <button class="portfolio-btn" data-filter="commercial">Commercial</button>
        </div>
        <div class="portfolio-media-row">
          @if(!$isHome)
          <div class="portfolio-media-tabs" data-portfolio-media-tabs>
            <button class="portfolio-media-btn active" data-media="all">All</button>
            <button class="portfolio-media-btn" data-media="photo">Photo</button>
            <button class="portfolio-media-btn" data-media="video">Video</button>
            <button class="portfolio-media-btn" data-media="drone">Drone</button>
          </div>
          <button class="portfolio-view" type="button" data-portfolio-open>View Gallery</button>
          @endif
        </div>
        @if($isHome)
        <div class="portfolio-mobile-controls" data-portfolio-mobile-controls hidden>
          <button class="portfolio-mobile-arrow" type="button" data-portfolio-mobile-prev aria-label="Previous slide">&#10094;</button>
          <span class="portfolio-mobile-count" data-portfolio-mobile-count>1 / 1</span>
          <button class="portfolio-mobile-arrow" type="button" data-portfolio-mobile-next aria-label="Next slide">&#10095;</button>
        </div>
        @endif

        <div class="portfolio-grid" data-portfolio-grid>
          <article class="portfolio-item" data-category="residential" data-media="photo">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos1.webp') }}" alt="Residential photo 1"></figure>
            <p class="portfolio-caption">Residential Property Photo</p>
          </article>
          <article class="portfolio-item" data-category="residential" data-media="video">
            <figure class="portfolio-media"><video class="lazy-video" autoplay muted loop playsinline preload="none"><source data-src="{{ asset('assets/media/webm/Videos1.webm') }}" type="video/webm"></video></figure>
            <p class="portfolio-caption">Residential Walkthrough Video</p>
          </article>
          <article class="portfolio-item" data-category="residential" data-media="drone">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone1.webp') }}" alt="Residential drone 1"></figure>
            <p class="portfolio-caption">Residential Drone View</p>
          </article>
          <article class="portfolio-item" data-category="residential" data-media="photo">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos2.webp') }}" alt="Residential photo 2"></figure>
            <p class="portfolio-caption">Residential Interior Photo</p>
          </article>
          <article class="portfolio-item" data-category="residential" data-media="video">
            <figure class="portfolio-media"><video class="lazy-video" autoplay muted loop playsinline preload="none"><source data-src="{{ asset('assets/media/webm/Videos2.webm') }}" type="video/webm"></video></figure>
            <p class="portfolio-caption">Residential Reel Clip</p>
          </article>
          <article class="portfolio-item" data-category="residential" data-media="drone">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone2.webp') }}" alt="Residential drone 2"></figure>
            <p class="portfolio-caption">Residential Aerial Angle</p>
          </article>

          <article class="portfolio-item hidden" data-category="commercial" data-media="photo">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos5.webp') }}" alt="Commercial photo 1"></figure>
            <p class="portfolio-caption">Commercial Property Photo</p>
          </article>
          <article class="portfolio-item hidden" data-category="commercial" data-media="video">
            <figure class="portfolio-media"><video class="lazy-video" autoplay muted loop playsinline preload="none"><source data-src="{{ asset('assets/media/webm/Videos3.webm') }}" type="video/webm"></video></figure>
            <p class="portfolio-caption">Commercial Walkthrough Video</p>
          </article>
          <article class="portfolio-item hidden" data-category="commercial" data-media="drone">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone3.webp') }}" alt="Commercial drone 1"></figure>
            <p class="portfolio-caption">Commercial Drone View</p>
          </article>
          <article class="portfolio-item hidden" data-category="commercial" data-media="photo">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/photos/photos6.webp') }}" alt="Commercial photo 2"></figure>
            <p class="portfolio-caption">Commercial Interior Photo</p>
          </article>
          <article class="portfolio-item hidden" data-category="commercial" data-media="video">
            <figure class="portfolio-media"><video class="lazy-video" autoplay muted loop playsinline preload="none"><source data-src="{{ asset('assets/media/webm/Videos4.webm') }}" type="video/webm"></video></figure>
            <p class="portfolio-caption">Commercial Promo Video</p>
          </article>
          <article class="portfolio-item hidden" data-category="commercial" data-media="drone">
            <figure class="portfolio-media"><img loading="lazy" decoding="async" src="{{ asset('assets/media/portfolio/drone/drone4.webp') }}" alt="Commercial drone 2"></figure>
            <p class="portfolio-caption">Commercial Aerial Angle</p>
          </article>
        </div>
      </div>
    </section>

    <div class="portfolio-lightbox" data-portfolio-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Portfolio gallery">
      <button class="portfolio-lightbox-close" type="button" data-portfolio-close aria-label="Close gallery">&times;</button>
      <button class="portfolio-lightbox-nav prev" type="button" data-portfolio-prev aria-label="Previous media">&#10094;</button>
      <div class="portfolio-lightbox-stage-wrap" data-portfolio-stage-wrap>
        <div class="portfolio-lightbox-stage" data-portfolio-stage></div>
        <p class="portfolio-lightbox-caption" data-portfolio-caption></p>
      </div>
      <button class="portfolio-lightbox-nav next" type="button" data-portfolio-next aria-label="Next media">&#10095;</button>
    </div>
    @endif

    @if($showPlan)
    <section id="packages" class="section packages">
      <div class="container">
        <div class="plan-heading-wrap">
          @if($isHome)
          <h2 class="section-title home-packages-title">Create Your Own Custom Plan</h2>
          <p class="section-sub">Pick a ready package or build your own with total control.</p>
          @else
          <h2 class="section-title plan-page-heading">Pick A Ready Package Or Build Your Own With Total Control.</h2>
          @endif
        </div>
        <div class="package-grid">
          <div class="package">
            <span class="package-ribbon">Showcase Ready</span>
            <div class="package-body">
              <h3>Essential</h3>
              <p class="package-start">Starting at</p>
              <p class="package-amount">$249.99</p>
              <p class="package-sub">Perfect for condos and standard listings.</p>
              <ul class="package-list">
                <li>Up to 30 HDR images</li>
                <li>Basic retouching</li>
                <li>24h delivery</li>
                <li>MLS-ready formatting</li>
              </ul>
              <a class="btn package-cta" href="{{ route('login') }}">Get Started</a>
            </div>
          </div>

          <div class="package package-popular">
            <span class="package-ribbon package-ribbon-red">Most Popular</span>
            <div class="package-body">
              <h3>Signature</h3>
              <p class="package-start">Starting at</p>
              <p class="package-amount">$349.99</p>
              <p class="package-sub">For listings that need stronger marketing.</p>
              <ul class="package-list">
                <li>Up to 25 HDR images</li>
                <li>Up to 7 drone images</li>
                <li>Video teaser (MLS + social)</li>
                <li>24h delivery</li>
              </ul>
              <a class="btn package-cta" href="{{ route('login') }}">Get Started</a>
            </div>
          </div>

          <div class="package">
            <span class="package-ribbon package-ribbon-dark">Premium</span>
            <div class="package-body">
              <h3>Prestige</h3>
              <p class="package-start">Starting at</p>
              <p class="package-amount">$499.99</p>
              <p class="package-sub">Full media coverage for premium properties.</p>
              <ul class="package-list">
                <li>Up to 30 HDR images</li>
                <li>Up to 10 drone images</li>
                <li>Cinematic walkthrough video</li>
                <li>Social reel cut + floor plan</li>
              </ul>
              <a class="btn package-cta" href="{{ route('login') }}">Get Started</a>
            </div>
          </div>

          <div class="package package-custom">
            <span class="package-ribbon package-ribbon-red">Create Your Own</span>
            <div class="package-body">
              <h3>Create Your Own Plan</h3>
              <p class="package-start">Starting at</p>
              <p class="package-amount">Custom</p>
              <p class="package-sub">Design a custom media mix that fits each listing and your brand goals.</p>
              <ul class="package-list">
                <li>Mix photo, video, and drone</li>
                <li>Virtual staging add-ons</li>
                <li>Day-to-dusk and object removal</li>
                <li>Flexible delivery timeline</li>
              </ul>
              <a class="btn package-cta" href="{{ route('login') }}">Build My Plan</a>
            </div>
          </div>
        </div>
        <div class="packages-included-card">
          <h3>Included</h3>
          <p>These perks are included with all our photography services, for a difference you can see.</p>
          <a class="btn package-cta included-mobile-cta" href="{{ route('login') }}">Get Started</a>
          <ul class="packages-included-list">
            <li>HDR</li>
            <li>Window masking</li>
            <li>Sky replacement</li>
            <li>Image on TV screen</li>
            <li>Fire in fireplace</li>
            <li>Personal image blurring</li>
            <li>Resized for social media and Centris</li>
          </ul>
        </div>
      </div>
    </section>

    <section id="video" class="section cinematic">
      <div class="container video-split">
        <div class="video-copy">
          <span class="video-badge">New</span>
          <h2 class="section-title">Turn Your Listing Photos into Reels in 24h.</h2>
          <p>AI photo-to-video that feels polished and premium. Send your photos, pick a style, and we deliver ready-to-post reels in a day.</p>
          <div class="video-cta">
            <a class="btn btn-primary" href="{{ route('login') }}">Get started</a>
            <a class="btn btn-ghost" href="{{ route('plan') }}">View packages</a>
          </div>
        </div>
        <div class="video-media">
          <div class="phone-shell">
            <span class="phone-notch" aria-hidden="true"></span>
            <video class="lazy-video" autoplay muted loop playsinline preload="none">
              <source data-src="{{ asset('assets/media/webm/Videos1.webm') }}" type="video/webm">
            </video>
          </div>
        </div>
      </div>
    </section>
    @endif

    @if($showServices)
    <section class="section process">
      <div class="container">
        <div class="process-head">
          <span class="process-kicker">How it works</span>
          <h2 class="section-title">Fast. Efficient. Professional.</h2>
          <p class="section-sub">From booking to delivery, every step is streamlined.</p>
        </div>
        <div class="timeline process-timeline">
          <div class="t-row t-row-right">
            <div class="t-visual t-visual-circle" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"></rect>
                <path d="M8 3.5V7"></path>
                <path d="M16 3.5V7"></path>
                <path d="M3.5 9.5H20.5"></path>
                <path d="M17.5 15.5H11"></path>
                <path d="M14.25 12.25V18.75"></path>
              </svg>
            </div>
            <div class="t-rail-point" aria-hidden="true"></div>
            <article class="t-card">
              <span class="t-step">01</span>
              <h3>Book your services</h3>
              <p>Schedule in under five minutes with clear pricing.</p>
            </article>
          </div>
          <div class="t-row t-row-left">
            <article class="t-card">
              <span class="t-step">02</span>
              <h3>Capture on site</h3>
              <p>Our crew handles photo, video, and drone in one visit.</p>
            </article>
            <div class="t-rail-point" aria-hidden="true"></div>
            <div class="t-visual t-visual-hex" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <rect x="3.5" y="7.5" width="17" height="11.5" rx="2.4"></rect>
                <circle cx="12" cy="13.2" r="3.4"></circle>
                <path d="M8 7.5L9.6 5.5H14.4L16 7.5"></path>
                <path d="M6.5 11.2H6.6"></path>
              </svg>
            </div>
          </div>
          <div class="t-row t-row-right">
            <div class="t-visual t-visual-triangle" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <circle cx="8.1" cy="9.2" r="2.6"></circle>
                <circle cx="15.9" cy="9.2" r="2.6"></circle>
                <path d="M10 10.9L18.8 18"></path>
                <path d="M14 10.9L5.2 18"></path>
                <path d="M11.2 14.1L12.8 12.5"></path>
              </svg>
            </div>
            <div class="t-rail-point" aria-hidden="true"></div>
            <article class="t-card">
              <span class="t-step">03</span>
              <h3>Expert editing</h3>
              <p>We enhance every image to a premium, consistent finish.</p>
            </article>
          </div>
          <div class="t-row t-row-left">
            <article class="t-card">
              <span class="t-step">04</span>
              <h3>Prompt delivery</h3>
              <p>Receive photos in 24-48h and video in 72h.</p>
            </article>
            <div class="t-rail-point" aria-hidden="true"></div>
            <div class="t-visual t-visual-diamond" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <rect x="4" y="6" width="16" height="12" rx="2"></rect>
                <path d="M9 12L11.2 14.2L15.5 9.8"></path>
                <path d="M7 20H17"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="broker-content" class="section broker-trust">
      <div class="container broker-trust-grid">
        <div class="broker-copy">
          <span class="broker-kicker">Broker Content</span>
          <h2 class="section-title">Built for Brokers Who Want to Lead with Authority</h2>
          <p class="section-sub">Professional on-camera content helps you build trust faster, strengthen your personal brand, and position yourself as the go-to expert for every listing.</p>
        </div>
        <div class="broker-media">
          <video class="lazy-video broker-video" autoplay muted loop playsinline preload="none" poster="{{ asset('assets/media/hero.webp') }}">
            <source data-src="{{ asset('assets/media/webm/Videos3.webm') }}" type="video/webm">
          </video>
        </div>
      </div>
    </section>
    @endif

    @if($showPortfolio)
    <section id="testimonials" class="section testimonials">
      <div class="container">
        <h2 class="section-title">Trusted by Leading Brokerages</h2>
        <div class="testimonial-carousel reveal-on-scroll" data-testimonial-carousel>
          <div class="testimonial-track" data-testimonial-track>
            <article class="testimonial-slide is-active" data-index="0">
              <div class="stars"><svg viewBox="0 0 110 20" width="90" height="16" aria-hidden="true"><g fill="currentColor"><path d="M10 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L10 1.5z"/><path d="M32 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L32 1.5z"/><path d="M54 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L54 1.5z"/><path d="M76 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L76 1.5z"/><path d="M98 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L98 1.5z"/></g></svg></div>
              <p class="testimonial-copy">Fast turnarounds and polished visuals that make every listing stand out.</p>
              <div class="testimonial-client">
                <span class="client-logo" aria-hidden="true">GP</span>
                <div>
                  <div class="name">Groupe Petra</div>
                  <div class="client-role">Residential Brokerage</div>
                </div>
              </div>
            </article>

            <article class="testimonial-slide" data-index="1">
              <div class="stars"><svg viewBox="0 0 110 20" width="90" height="16" aria-hidden="true"><g fill="currentColor"><path d="M10 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L10 1.5z"/><path d="M32 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L32 1.5z"/><path d="M54 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L54 1.5z"/><path d="M76 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L76 1.5z"/><path d="M98 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L98 1.5z"/></g></svg></div>
              <p class="testimonial-copy">Consistent quality across every shoot. Easy team to work with.</p>
              <div class="testimonial-client">
                <span class="client-logo" aria-hidden="true">GM</span>
                <div>
                  <div class="name">Groupe Mach</div>
                  <div class="client-role">Commercial Real Estate</div>
                </div>
              </div>
            </article>

            <article class="testimonial-slide" data-index="2">
              <div class="stars"><svg viewBox="0 0 110 20" width="90" height="16" aria-hidden="true"><g fill="currentColor"><path d="M10 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L10 1.5z"/><path d="M32 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L32 1.5z"/><path d="M54 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L54 1.5z"/><path d="M76 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L76 1.5z"/><path d="M98 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L98 1.5z"/></g></svg></div>
              <p class="testimonial-copy">Video and drone assets that elevate our brand presentation.</p>
              <div class="testimonial-client">
                <span class="client-logo" aria-hidden="true">BTB</span>
                <div>
                  <div class="name">BTB</div>
                  <div class="client-role">Property Marketing Team</div>
                </div>
              </div>
            </article>

            <article class="testimonial-slide" data-index="3">
              <div class="stars"><svg viewBox="0 0 110 20" width="90" height="16" aria-hidden="true"><g fill="currentColor"><path d="M10 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L10 1.5z"/><path d="M32 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L32 1.5z"/><path d="M54 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L54 1.5z"/><path d="M76 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L76 1.5z"/><path d="M98 1.5l2.6 5.3 5.8.8-4.2 4 1 5.7-5.2-2.8-5.2 2.8 1-5.7-4.2-4 5.8-.8L98 1.5z"/></g></svg></div>
              <p class="testimonial-copy">Booking is simple, delivery is reliable, and results stay premium.</p>
              <div class="testimonial-client">
                <span class="client-logo" aria-hidden="true">TD</span>
                <div>
                  <div class="name">Tidan</div>
                  <div class="client-role">Development Group</div>
                </div>
              </div>
            </article>
          </div>

          <div class="testimonial-controls">
            <button class="testimonial-nav" type="button" data-testimonial-prev aria-label="Previous testimonial">&#10094;</button>
            <div class="testimonial-dots" role="tablist" aria-label="Testimonials pagination">
              <button class="testimonial-dot is-active" type="button" data-testimonial-dot="0" aria-label="Go to testimonial 1"></button>
              <button class="testimonial-dot" type="button" data-testimonial-dot="1" aria-label="Go to testimonial 2"></button>
              <button class="testimonial-dot" type="button" data-testimonial-dot="2" aria-label="Go to testimonial 3"></button>
              <button class="testimonial-dot" type="button" data-testimonial-dot="3" aria-label="Go to testimonial 4"></button>
            </div>
            <button class="testimonial-nav" type="button" data-testimonial-next aria-label="Next testimonial">&#10095;</button>
          </div>
        </div>
      </div>
    </section>

    <section class="section brand-strip">
      <div class="container">
        <h2 class="section-title">Trusted by Leading Brands Worldwide</h2>
        <div class="brand-marquee" aria-label="Partner logos">
          <div class="brand-track">
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (1).webp') }}" alt="Partner logo 1"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (2).webp') }}" alt="Partner logo 2"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (3).webp') }}" alt="Partner logo 3"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (4).webp') }}" alt="Partner logo 4"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (5).webp') }}" alt="Partner logo 5"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (6).webp') }}" alt="Partner logo 6"></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (7).webp') }}" alt="Partner logo 7"></div>
          </div>
          <div class="brand-track" aria-hidden="true">
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (1).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (2).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (3).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (4).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (5).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (6).webp') }}" alt=""></div>
            <div class="brand-logo"><img loading="lazy" decoding="async" src="{{ asset('assets/media/company_logo/company_logo (7).webp') }}" alt=""></div>
          </div>
        </div>
      </div>
    </section>
    @endif

    @if($showPlan)
    <section class="section final-cta" aria-labelledby="final-cta-title">
      <div class="container">
        <div class="final-cta-card">
          <h2 id="final-cta-title" class="final-cta-title">Unlock Visual Brilliance</h2>
          <a class="btn btn-primary final-cta-btn" href="{{ route('login') }}">Book Now</a>
        </div>
      </div>
    </section>
    @endif

    <section id="contact" class="section contact">
      <div class="container contact-grid">
        <div class="contact-intro">
          <strong class="contact-title">Let's Create Something that Sets Your Listing Apart</strong>
          <p class="contact-lead">From premium real estate media to strategic branding content and lead-focused visuals, Maccento Real Estate Media delivers refined, high-impact content designed to elevate your image and position your properties at their best.</p>
        </div>
        <form class="form">
          <div class="form-grid">
            <input class="input" placeholder="Full Name" type="text" name="name">
            <input class="input" placeholder="Agency or Company?" type="text" name="company">
            <input class="input" placeholder="Phone" type="tel" name="phone">
            <input class="input" placeholder="E-mail" type="email" name="email">
            <div class="custom-select" data-select>
              <input type="hidden" name="service">
              <button class="input custom-select-trigger" type="button" aria-expanded="false">Services required?</button>
              <ul class="custom-select-menu" role="listbox">
                <li class="custom-select-option" data-value="Photography">Photography</li>
                <li class="custom-select-option" data-value="Videography">Videography</li>
                <li class="custom-select-option" data-value="Drone">Drone</li>
                <li class="custom-select-option" data-value="3D">3D</li>
                <li class="custom-select-option" data-value="Virtual staging">Virtual staging</li>
                <li class="custom-select-option" data-value="Other services">Other services</li>
              </ul>
            </div>
            <div class="custom-select" data-select>
              <input type="hidden" name="region">
              <button class="input custom-select-trigger" type="button" aria-expanded="false">Region</button>
              <ul class="custom-select-menu" role="listbox">
                <li class="custom-select-option" data-value="Montreal">Montreal</li>
                <li class="custom-select-option" data-value="Laval">Laval</li>
                <li class="custom-select-option" data-value="South Shore">South Shore</li>
                <li class="custom-select-option" data-value="North Shore">North Shore</li>
                <li class="custom-select-option" data-value="Other">Other</li>
              </ul>
            </div>
          </div>
          <textarea class="input" placeholder="Message" name="message"></textarea>
          <button class="btn contact-submit" type="submit">Contact Us Today!</button>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-inner">
      <img loading="lazy" decoding="async" class="footer-logo" src="{{ asset('assets/media/logo-footer.png') }}" alt="Maccento logo">
      <p class="footer-desc">One-stop real estate content studio serving brokers across Montreal and beyond.</p>
      <div class="footer-social" aria-label="Social media">
        <a class="social-link" href="#" aria-label="Instagram">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M7 3.5h10A3.5 3.5 0 0 1 20.5 7v10A3.5 3.5 0 0 1 17 20.5H7A3.5 3.5 0 0 1 3.5 17V7A3.5 3.5 0 0 1 7 3.5z"/><circle cx="12" cy="12" r="3.6" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor"/></svg>
        </a>
        <a class="social-link" href="#" aria-label="LinkedIn">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.2 8.7H3.5V20h2.7V8.7zm-1.3-4A1.6 1.6 0 1 0 5 8a1.6 1.6 0 0 0-.1-3.2zM20.5 20v-6.4c0-3.1-1.7-4.6-4-4.6-1.8 0-2.7 1-3.2 1.7V8.7h-2.7V20h2.7v-6.3c0-1.7.9-2.8 2.4-2.8s2.1 1.1 2.1 2.8V20h2.7z"/></svg>
        </a>
        <a class="social-link" href="#" aria-label="Facebook">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.6 20v-7h2.4l.4-2.8h-2.8V8.4c0-.8.2-1.4 1.4-1.4h1.5V4.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2H8.2V13h2.4v7h3z"/></svg>
        </a>
        <a class="social-link" href="#" aria-label="YouTube">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3.2" y="5.8" width="17.6" height="12.4" rx="3.2" fill="none" stroke="currentColor" stroke-width="1.8"/>
            <path fill="currentColor" d="M10 9.2v5.6l5-2.8-5-2.8z"/>
          </svg>
        </a>
      </div>
      <div class="footer-divider" aria-hidden="true"></div>
      <div class="footer-contact-line">
        <a class="footer-contact-item" href="mailto:info@maccento.ca">
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="2.5" y="5" width="19" height="14" rx="2.8" fill="none" stroke="currentColor" stroke-width="2.2"/>
              <path d="M3.8 7.2L12 13l8.2-5.8" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="contact-text">info@maccento.ca</span>
        </a>
        <span class="footer-contact-dot" aria-hidden="true">•</span>
        <a class="footer-contact-item" href="tel:+15149519141">
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6.4 3.1h3.6c.7 0 1.3.5 1.5 1.2l1 3.8c.2.7-.1 1.5-.7 1.9l-1.9 1.2a14.3 14.3 0 0 0 3 3 14.3 14.3 0 0 0 3 2l1.2-1.9c.4-.6 1.2-.9 1.9-.7l3.8 1c.7.2 1.2.8 1.2 1.5V19c0 1.1-.9 2-2 2C10.6 21 3 13.4 3 6.4c0-1.1.9-2 2-2z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="contact-text">(514) 951-9141</span>
        </a>
      </div>
      <div class="footer-bottom">&copy; 2026 Maccento Real Estate Media</div>
    </div>
  </footer>

  <script>
    (function () {
      const portfolioButtons = document.querySelectorAll('.portfolio-btn');
      const portfolioMediaButtons = document.querySelectorAll('.portfolio-media-btn');
      const portfolioItems = document.querySelectorAll('.portfolio-item');
      const portfolioGrid = document.querySelector('[data-portfolio-grid]');
      const portfolioSection = document.querySelector('#portfolio');
      const portfolioOpenBtn = document.querySelector('[data-portfolio-open]');
      const portfolioMobileControls = document.querySelector('[data-portfolio-mobile-controls]');
      const portfolioMobilePrev = document.querySelector('[data-portfolio-mobile-prev]');
      const portfolioMobileNext = document.querySelector('[data-portfolio-mobile-next]');
      const portfolioMobileCount = document.querySelector('[data-portfolio-mobile-count]');
      const lightbox = document.querySelector('[data-portfolio-lightbox]');
      const lightboxStage = document.querySelector('[data-portfolio-stage]');
      const lightboxCaption = document.querySelector('[data-portfolio-caption]');
      const lightboxClose = document.querySelector('[data-portfolio-close]');
      const lightboxPrev = document.querySelector('[data-portfolio-prev]');
      const lightboxNext = document.querySelector('[data-portfolio-next]');
      const lightboxStageWrap = document.querySelector('[data-portfolio-stage-wrap]');
      const getFilterIndex = (filter) => Array.from(portfolioButtons).findIndex(
        (btn) => btn.getAttribute('data-filter') === filter
      );
      const loadVideo = (video) => {
        if (!video || video.dataset.loaded === 'true') return;

        const source = video.querySelector('source[data-src]');
        if (!source) return;

        source.src = source.dataset.src;
        source.removeAttribute('data-src');
        video.dataset.loaded = 'true';
        video.load();

        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(() => {});
        }
      };

      const lazyVideos = document.querySelectorAll('video.lazy-video');
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              loadVideo(entry.target);
              obs.unobserve(entry.target);
            }
          });
        }, { rootMargin: '200px 0px' });

        lazyVideos.forEach((video) => observer.observe(video));
      } else {
        lazyVideos.forEach((video) => loadVideo(video));
      }

      let activePortfolioFilter = 'residential';
      let activeMediaFilter = 'all';
      let lightboxItems = [];
      let lightboxIndex = 0;
      let mobilePortfolioIndex = 0;
      const isHomePortfolioSection = Boolean(portfolioSection && portfolioSection.classList.contains('portfolio-home'));
      const isMobileHomePortfolio = () => isHomePortfolioSection && window.innerWidth <= 640;

      const getVisiblePortfolioItems = () => Array.from(portfolioItems).filter(
        (item) => !item.classList.contains('hidden')
      );
      const syncMobilePortfolioCarousel = () => {
        const visibleItems = getVisiblePortfolioItems();

        portfolioItems.forEach((item) => {
          item.classList.remove('mobile-slide-hidden', 'mobile-slide-active');
        });

        if (!isMobileHomePortfolio()) {
          if (portfolioMobileControls) portfolioMobileControls.hidden = true;
          return;
        }

        if (portfolioMobileControls) portfolioMobileControls.hidden = false;
        if (!visibleItems.length) return;

        mobilePortfolioIndex = ((mobilePortfolioIndex % visibleItems.length) + visibleItems.length) % visibleItems.length;
        visibleItems.forEach((item, index) => {
          const active = index === mobilePortfolioIndex;
          item.classList.toggle('mobile-slide-active', active);
          item.classList.toggle('mobile-slide-hidden', !active);
        });

        if (portfolioMobileCount) {
          portfolioMobileCount.textContent = `${mobilePortfolioIndex + 1} / ${visibleItems.length}`;
        }
      };

      const closeLightbox = () => {
        if (!lightbox || lightbox.hidden) return;
        lightbox.hidden = true;
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        if (lightboxStage) lightboxStage.innerHTML = '';
      };

      const renderLightbox = (index) => {
        if (!lightboxStage || !lightboxCaption || !lightboxItems.length) return;

        lightboxIndex = (index + lightboxItems.length) % lightboxItems.length;
        const item = lightboxItems[lightboxIndex];
        const mediaType = item.getAttribute('data-media') || 'photo';
        const captionText = (item.querySelector('.portfolio-caption')?.textContent || '').trim();
        lightboxStage.innerHTML = '';

        if (mediaType === 'video') {
          const sourceEl = item.querySelector('video source');
          const sourceSrc = sourceEl ? (sourceEl.getAttribute('src') || sourceEl.getAttribute('data-src') || '') : '';
          const video = document.createElement('video');
          video.controls = true;
          video.autoplay = true;
          video.playsInline = true;
          video.muted = true;
          video.loop = true;
          video.className = 'portfolio-lightbox-media';
          const source = document.createElement('source');
          source.src = sourceSrc;
          source.type = 'video/webm';
          video.appendChild(source);
          lightboxStage.appendChild(video);
          video.load();
          const playPromise = video.play();
          if (playPromise && typeof playPromise.catch === 'function') playPromise.catch(() => {});
        } else {
          const imgEl = item.querySelector('img');
          if (!imgEl) return;
          const img = document.createElement('img');
          img.className = 'portfolio-lightbox-media';
          img.src = imgEl.currentSrc || imgEl.src;
          img.alt = imgEl.alt || 'Portfolio media';
          lightboxStage.appendChild(img);
        }

        lightboxCaption.textContent = captionText;
      };

      const openLightbox = (startIndex = 0) => {
        if (!lightbox) return;
        lightboxItems = getVisiblePortfolioItems();
        if (!lightboxItems.length) return;
        lightbox.hidden = false;
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
        renderLightbox(startIndex);
      };

      function setActive(categoryFilter, mediaFilter = activeMediaFilter, immediate = false) {
        const previousIndex = getFilterIndex(activePortfolioFilter);
        const nextIndex = getFilterIndex(categoryFilter);
        const noChange = categoryFilter === activePortfolioFilter && mediaFilter === activeMediaFilter;
        if (!immediate && noChange) return;
        if (categoryFilter !== activePortfolioFilter || mediaFilter !== activeMediaFilter) {
          mobilePortfolioIndex = 0;
        }

        const leavingItems = [];
        const enteringItems = [];
        const isEnhancedPortfolio = Boolean(portfolioGrid);
        const leaveDelay = isEnhancedPortfolio ? 220 : 110;
        const firstEnterDelay = isEnhancedPortfolio ? 90 : 24;
        const enterStagger = isEnhancedPortfolio ? 120 : 24;
        const enterDuration = isEnhancedPortfolio ? 620 : 260;

        portfolioItems.forEach((item) => {
          const cat = item.getAttribute('data-category');
          const media = item.getAttribute('data-media');
          const matchesMedia = mediaFilter === 'all' || media === mediaFilter;
          const isActive = cat === categoryFilter && matchesMedia;
          if (isActive) enteringItems.push(item);
          else if (!item.classList.contains('hidden')) leavingItems.push(item);
        });
        const switchResetDelay = isEnhancedPortfolio
          ? firstEnterDelay + (enteringItems.length * enterStagger) + enterDuration + 120
          : 220;

        if (!immediate && portfolioGrid) {
          portfolioGrid.classList.add('is-switching');
          portfolioGrid.classList.remove('is-slide-left', 'is-slide-right');
          if (nextIndex > previousIndex) portfolioGrid.classList.add('is-slide-left');
          if (nextIndex < previousIndex) portfolioGrid.classList.add('is-slide-right');
        }

        if (immediate) {
          portfolioItems.forEach((item) => {
            const cat = item.getAttribute('data-category');
            const media = item.getAttribute('data-media');
            const matchesMedia = mediaFilter === 'all' || media === mediaFilter;
            const isActive = cat === categoryFilter && matchesMedia;
            item.classList.toggle('hidden', !isActive);
            item.classList.toggle('is-active', isActive);
            item.classList.remove('is-entering', 'is-leaving');
            if (isActive) {
              const video = item.querySelector('video.lazy-video');
              loadVideo(video);
            }
          });
          activePortfolioFilter = categoryFilter;
          activeMediaFilter = mediaFilter;
          if (portfolioGrid) portfolioGrid.classList.remove('is-switching', 'is-slide-left', 'is-slide-right');
          syncMobilePortfolioCarousel();
          return;
        }

        leavingItems.forEach((item) => {
          item.classList.remove('is-active', 'is-entering');
          item.classList.add('is-leaving');
        });

        window.setTimeout(() => {
          leavingItems.forEach((item) => {
            item.classList.add('hidden');
            item.classList.remove('is-leaving');
          });

          enteringItems.forEach((item, index) => {
            item.classList.remove('hidden', 'is-leaving');
            item.classList.add('is-entering');
            const video = item.querySelector('video.lazy-video');
            loadVideo(video);

            window.setTimeout(() => {
              window.requestAnimationFrame(() => {
                item.classList.add('is-active');
                item.classList.remove('is-entering');
              });
            }, firstEnterDelay + (index * enterStagger));
          });

          window.setTimeout(() => {
            if (portfolioGrid) portfolioGrid.classList.remove('is-switching', 'is-slide-left', 'is-slide-right');
            syncMobilePortfolioCarousel();
          }, switchResetDelay);
        }, leaveDelay);

        activePortfolioFilter = categoryFilter;
        activeMediaFilter = mediaFilter;
      }
      portfolioButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          portfolioButtons.forEach((b) => b.classList.remove('active'));
          btn.classList.add('active');
          setActive(btn.getAttribute('data-filter'), activeMediaFilter, isMobileHomePortfolio());
        });
      });
      portfolioMediaButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          portfolioMediaButtons.forEach((b) => b.classList.remove('active'));
          btn.classList.add('active');
          setActive(activePortfolioFilter, btn.getAttribute('data-media'), isMobileHomePortfolio());
        });
      });
      if (portfolioItems.length) {
        setActive('residential', 'all', true);
      }
      if (portfolioMobilePrev) {
        portfolioMobilePrev.addEventListener('click', () => {
          mobilePortfolioIndex -= 1;
          syncMobilePortfolioCarousel();
        });
      }
      if (portfolioMobileNext) {
        portfolioMobileNext.addEventListener('click', () => {
          mobilePortfolioIndex += 1;
          syncMobilePortfolioCarousel();
        });
      }
      window.addEventListener('resize', () => {
        syncMobilePortfolioCarousel();
      });

      if (portfolioOpenBtn && lightbox) {
        portfolioOpenBtn.addEventListener('click', () => openLightbox(0));
      }
      if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
      }
      if (lightboxPrev) {
        lightboxPrev.addEventListener('click', () => renderLightbox(lightboxIndex - 1));
      }
      if (lightboxNext) {
        lightboxNext.addEventListener('click', () => renderLightbox(lightboxIndex + 1));
      }
      portfolioItems.forEach((item) => {
        item.addEventListener('click', () => {
          const visibleItems = getVisiblePortfolioItems();
          const idx = visibleItems.indexOf(item);
          if (idx >= 0) openLightbox(idx);
        });
      });
      if (lightbox) {
        lightbox.addEventListener('click', (event) => {
          if (event.target === lightbox || event.target === lightboxStageWrap) closeLightbox();
        });
      }
      document.addEventListener('keydown', (event) => {
        if (!lightbox || lightbox.hidden) return;
        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowLeft') renderLightbox(lightboxIndex - 1);
        if (event.key === 'ArrowRight') renderLightbox(lightboxIndex + 1);
      });

      // Before/After compare sliders (desktop + mobile drag/tap)
      const compareBlocks = Array.from(document.querySelectorAll('[data-compare]'));
      if (compareBlocks.length) {
        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const setComparePosition = (block, percent) => {
          const next = clamp(percent, 8, 92);
          block.style.setProperty('--compare-pos', `${next}%`);
          const handle = block.querySelector('.compare-handle');
          if (handle) handle.setAttribute('aria-valuenow', String(Math.round(next)));
        };

        const positionFromClientX = (block, clientX) => {
          const rect = block.getBoundingClientRect();
          if (!rect.width) return 50;
          return ((clientX - rect.left) / rect.width) * 100;
        };

        compareBlocks.forEach((block) => {
          setComparePosition(block, 50);
          const handle = block.querySelector('.compare-handle');
          if (!handle) return;

          let dragging = false;
          const startDrag = (clientX) => {
            dragging = true;
            block.classList.add('is-dragging');
            setComparePosition(block, positionFromClientX(block, clientX));
          };
          const moveDrag = (clientX) => {
            if (!dragging) return;
            setComparePosition(block, positionFromClientX(block, clientX));
          };
          const endDrag = () => {
            dragging = false;
            block.classList.remove('is-dragging');
          };

          const onPointerDown = (event) => {
            event.preventDefault();
            startDrag(event.clientX);
            block.setPointerCapture(event.pointerId);
          };
          const onPointerMove = (event) => moveDrag(event.clientX);
          const onPointerUp = () => endDrag();

          handle.addEventListener('pointerdown', onPointerDown);
          block.addEventListener('pointermove', onPointerMove);
          block.addEventListener('pointerup', onPointerUp);
          block.addEventListener('pointercancel', onPointerUp);
          block.addEventListener('pointerleave', onPointerUp);

          block.addEventListener('click', (event) => {
            if (event.target.closest('.compare-handle')) return;
            setComparePosition(block, positionFromClientX(block, event.clientX));
          });
        });
      }

      // Services page: mobile-only expand/collapse via arrow button
      const serviceCards = Array.from(document.querySelectorAll('.services-page .service-card'));
      const serviceToggleButtons = Array.from(document.querySelectorAll('.services-page .service-toggle'));
      if (serviceCards.length && serviceToggleButtons.length) {
        const isMobileServices = () => window.innerWidth <= 640;

        const closeAllServiceCards = () => {
          serviceCards.forEach((card) => {
            card.classList.remove('is-open');
            const btn = card.querySelector('.service-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
          });
        };

        serviceToggleButtons.forEach((btn) => {
          btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (!isMobileServices()) return;

            const card = btn.closest('.service-card');
            if (!card) return;

            const shouldOpen = !card.classList.contains('is-open');
            closeAllServiceCards();
            if (shouldOpen) {
              card.classList.add('is-open');
              btn.setAttribute('aria-expanded', 'true');
            }
          });
        });

        const syncServiceCardsByViewport = () => {
          if (!isMobileServices()) {
            closeAllServiceCards();
          }
        };
        syncServiceCardsByViewport();
        window.addEventListener('resize', syncServiceCardsByViewport);
      }

      // Light scroll reveal for cleaner visual pacing
      const sectionRevealTargets = Array.from(document.querySelectorAll('main > section.section'));
      sectionRevealTargets.forEach((el) => el.classList.add('section-reveal'));

      const revealTargets = Array.from(document.querySelectorAll(
        '.why-item, .offer, .service-card, .example-card, .portfolio-head, .portfolio-item, .package, .testimonial-carousel, .t-card, .video-copy, .video-media, .broker-copy, .broker-media, .contact-intro, .form'
      ));
      revealTargets.forEach((el) => el.classList.add('reveal-on-scroll'));

      // Stagger content inside each section for a more intentional reveal sequence.
      sectionRevealTargets.forEach((section) => {
        const stagedItems = section.querySelectorAll('.reveal-on-scroll');
        const isFastSection = section.classList.contains('why-maccento') || section.classList.contains('offerings');
        const staggerStep = isFastSection ? 55 : 120;
        const staggerCap = isFastSection ? 260 : 540;
        stagedItems.forEach((el, index) => {
          if (el.style.transitionDelay) return;
          el.style.transitionDelay = `${Math.min(index * staggerStep, staggerCap)}ms`;
        });
      });

      if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          });
        }, { threshold: 0.08, rootMargin: '0px 0px -10px 0px' });

        sectionRevealTargets.forEach((el) => revealObserver.observe(el));
        revealTargets.forEach((el) => revealObserver.observe(el));

      } else {
        sectionRevealTargets.forEach((el) => el.classList.add('is-visible'));
        revealTargets.forEach((el) => el.classList.add('is-visible'));
      }

      // Process timeline line fill based on real scroll progress.
      const processSection = document.querySelector('.process');
      const prefersReducedMotionTimeline = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (processSection) {
        const updateProcessLineFill = () => {
          const rect = processSection.getBoundingClientRect();
          const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
          const start = viewportHeight * 0.9;
          const end = viewportHeight * 0.2;
          const progressRaw = (start - rect.top) / Math.max(1, (start - end) + rect.height * 0.2);
          const progress = Math.max(0, Math.min(1, progressRaw));
          processSection.style.setProperty('--process-line-fill', progress.toFixed(3));
        };

        if (prefersReducedMotionTimeline) {
          processSection.style.setProperty('--process-line-fill', '1');
        } else {
          let processTicking = false;
          const onProcessScroll = () => {
            if (processTicking) return;
            processTicking = true;
            window.requestAnimationFrame(() => {
              processTicking = false;
              updateProcessLineFill();
            });
          };
          updateProcessLineFill();
          window.addEventListener('scroll', onProcessScroll, { passive: true });
          window.addEventListener('resize', onProcessScroll);
        }
      }

      // Stagger perks so they appear one-by-one
      const perkItems = document.querySelectorAll('.why-list .why-item');
      perkItems.forEach((item, index) => {
        item.style.transitionDelay = `${index * 90}ms`;
      });

      // Light parallax for perk icons while scrolling
      const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const whySection = document.querySelector('.why-maccento');
      const perkIcons = document.querySelectorAll('.why-list .why-icon');
      if (!prefersReducedMotion && whySection && perkIcons.length) {
        let ticking = false;
        const applyPerkParallax = () => {
          ticking = false;
          const rect = whySection.getBoundingClientRect();
          const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
          const progressRaw = (viewportHeight - rect.top) / (viewportHeight + rect.height);
          const progress = Math.max(0, Math.min(1, progressRaw));

          perkIcons.forEach((icon, index) => {
            const offset = (progress - 0.5) * (8 + index * 1.4);
            icon.style.transform = `translateY(${offset.toFixed(2)}px)`;
          });
        };

        const onScroll = () => {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(applyPerkParallax);
        };

        applyPerkParallax();
        window.addEventListener('scroll', onScroll, { passive: true });
      }

      // Broker video: autoplay only while visible
      const brokerVideo = document.querySelector('.broker-video');
      if (brokerVideo) {
        const handleBrokerVisibility = (isVisible) => {
          if (isVisible) {
            loadVideo(brokerVideo);
            const playPromise = brokerVideo.play();
            if (playPromise && typeof playPromise.catch === 'function') playPromise.catch(() => {});
            return;
          }
          brokerVideo.pause();
        };

        if ('IntersectionObserver' in window) {
          const brokerObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => handleBrokerVisibility(entry.isIntersecting));
          }, { threshold: 0.45 });
          brokerObserver.observe(brokerVideo);
        } else {
          handleBrokerVisibility(true);
        }
      }

      // Testimonials carousel
      const testimonialCarousel = document.querySelector('[data-testimonial-carousel]');
      if (testimonialCarousel) {
        const slides = Array.from(testimonialCarousel.querySelectorAll('.testimonial-slide'));
        const dotsContainer = testimonialCarousel.querySelector('.testimonial-dots');
        const prevBtn = testimonialCarousel.querySelector('[data-testimonial-prev]');
        const nextBtn = testimonialCarousel.querySelector('[data-testimonial-next]');
        let dots = [];
        let currentSlide = 0;
        let visibleSlides = 3;
        let maxSlide = 0;
        let autoplayId = null;
        const autoplayDelay = 5000;

        const getVisibleSlides = () => {
          if (window.innerWidth <= 640) return 1;
          if (window.innerWidth <= 980) return 2;
          return 3;
        };

        const buildDots = () => {
          if (!dotsContainer) return;
          dotsContainer.innerHTML = '';
          dots = [];
          for (let i = 0; i <= maxSlide; i += 1) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'testimonial-dot';
            if (i === currentSlide) dot.classList.add('is-active');
            dot.setAttribute('aria-label', `Go to testimonial ${i + 1}`);
            dot.addEventListener('click', () => {
              showSlide(i);
            });
            dotsContainer.appendChild(dot);
            dots.push(dot);
          }
        };

        const updateMetrics = () => {
          visibleSlides = getVisibleSlides();
          maxSlide = Math.max(0, slides.length - visibleSlides);
          currentSlide = Math.min(currentSlide, maxSlide);
          buildDots();
          showSlide(currentSlide, true);
        };

        const showSlide = (index) => {
          if (index > maxSlide) currentSlide = 0;
          else if (index < 0) currentSlide = maxSlide;
          else currentSlide = index;

          const track = testimonialCarousel.querySelector('[data-testimonial-track]');
          const step = slides.length > 1 ? (slides[1].offsetLeft - slides[0].offsetLeft) : 0;
          if (track) {
            track.style.transform = `translateX(${-currentSlide * step}px)`;
          }

          dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === currentSlide);
          });
        };

        const startAutoplay = () => {
          if (autoplayId) return;
          autoplayId = window.setInterval(() => {
            showSlide(currentSlide + 1);
          }, autoplayDelay);
        };

        const stopAutoplay = () => {
          if (!autoplayId) return;
          window.clearInterval(autoplayId);
          autoplayId = null;
        };

        dots.forEach((dot, index) => {
          dot.addEventListener('click', () => {
            showSlide(index);
          });
        });

        if (prevBtn) {
          prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
        }
        if (nextBtn) {
          nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
        }

        testimonialCarousel.addEventListener('mouseenter', stopAutoplay);
        testimonialCarousel.addEventListener('mouseleave', startAutoplay);
        testimonialCarousel.addEventListener('focusin', stopAutoplay);
        testimonialCarousel.addEventListener('focusout', startAutoplay);

        updateMetrics();
        window.addEventListener('resize', updateMetrics);
        startAutoplay();
      }

      const header = document.querySelector('.header');
      const toggle = document.querySelector('.nav-toggle');
      const mobileMenu = document.querySelector('#mobile-menu');
      const mobileLinks = mobileMenu ? mobileMenu.querySelectorAll('a') : [];
      if (header && toggle && mobileMenu) {
        toggle.addEventListener('click', () => {
          const isOpen = header.classList.toggle('menu-open');
          toggle.setAttribute('aria-expanded', String(isOpen));
          mobileMenu.setAttribute('aria-hidden', String(!isOpen));
        });
        mobileLinks.forEach((link) => {
          link.addEventListener('click', () => {
            header.classList.remove('menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
          });
        });
        window.addEventListener('resize', () => {
          if (window.innerWidth > 980) {
            header.classList.remove('menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
          }
        });
      }

      const customSelects = document.querySelectorAll('[data-select]');
      customSelects.forEach((select) => {
        const trigger = select.querySelector('.custom-select-trigger');
        const hiddenInput = select.querySelector('input[type="hidden"]');
        const options = select.querySelectorAll('.custom-select-option');
        if (!trigger || !hiddenInput || options.length === 0) return;

        const close = () => {
          select.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
        };

        trigger.addEventListener('click', () => {
          const willOpen = !select.classList.contains('open');
          customSelects.forEach((item) => {
            item.classList.remove('open');
            const itemTrigger = item.querySelector('.custom-select-trigger');
            if (itemTrigger) itemTrigger.setAttribute('aria-expanded', 'false');
          });
          if (willOpen) {
            select.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
          }
        });

        options.forEach((option) => {
          option.addEventListener('click', () => {
            options.forEach((opt) => opt.classList.remove('active'));
            option.classList.add('active');
            trigger.textContent = option.textContent || '';
            hiddenInput.value = option.getAttribute('data-value') || '';
            close();
          });
        });

        document.addEventListener('click', (event) => {
          if (!select.contains(event.target)) close();
        });
      });

      const frTranslations = {
        'Maccento | Real Estate Media': 'Maccento | Media Immobilier',
        'Services': 'Services',
        'Our Services': 'Nos services',
        'ABOUT US': 'A PROPOS',
        'OUR SERVICES': 'NOS SERVICES',
        'Portfolio': 'Portfolio',
        'PORTFOLIO': 'PORTFOLIO',
        'OUR PLAN': 'NOTRE FORFAIT',
        'Our Plan': 'Notre forfait',
        'Packages': 'Forfaits',
        'Image to Video': 'Image vers Video',
        'Testimonials': 'Temoignages',
        'Contact': 'Contact',
        'Book a shoot': 'Reserver une seance',
        'Book Now': 'Reserver maintenant',
        'Pioneers of Real Estate Listing Intelligence': 'Pionniers de l intelligence des\ninscriptions immobilieres',
        'We build marketing systems that help brokers launch faster, present properties better, and keep quality consistent across every listing. From capture to delivery, every step is designed for speed, clarity, and premium output.': 'Nous construisons des systemes marketing qui aident les courtiers a lancer plus vite, mieux presenter les proprietes et maintenir une qualite constante sur chaque inscription. De la capture a la livraison, chaque etape est pensee pour la rapidite, la clarte et un rendu premium.',
        'Get a demo': 'Demander une demo',
        'See our plans': 'Voir nos forfaits',
        'The Real Estate Media Partner For Brokers': 'Le partenaire media immobilier des courtiers',
        'On-site media across Greater Montreal. Remote editing for brokers anywhere.': 'Media sur place dans tout le Grand Montreal. Montage a distance pour les courtiers partout.',
        'View packages': 'Voir les forfaits',
        'View Packages': 'Voir les forfaits',
        '24-48H Delivery': 'Livraison en 24-48 h',
        'Licensed Drone Pilots': 'Pilotes de drone certifies',
        'MLS-Ready Assets': 'Contenus prets pour le MLS',
        'Why Maccento': 'Pourquoi Maccento',
        'Speed, consistency, and a premium look © without juggling multiple vendors.': 'Vitesse, constance et rendu premium, sans gerer plusieurs fournisseurs.',
        'A Smarter Approach to Real Estate Media': 'Une approche plus intelligente du media immobilier',
        'Elevated visuals. Seamless execution. Consistent results.': 'Des visuels eleves. Une execution fluide. Des resultats constants.',
        'Tailored Creative Approach': 'Approche creative sur mesure',
        'Every project is shaped around your vision, your timeline, and the unique character of each space - never a one-size-fits-all process.': 'Chaque projet est concu selon votre vision, votre calendrier et le caractere unique de chaque espace - jamais une methode standardisee.',
        'Fast, Reliable Turnaround': 'Delai rapide et fiable',
        'Streamlined workflows allow us to deliver polished visuals quickly without compromising quality or consistency.': 'Des flux de travail optimises nous permettent de livrer rapidement des visuels soignes sans compromettre la qualite ni la constance.',
        'Certified & Fully Insured Drone Operations': 'Operations drone certifiees et entierement assurees',
        'Transport Canada-licensed pilots ensure safe, compliant, and cinematic aerial perspectives on every shoot.': 'Des pilotes agrees par Transports Canada garantissent des prises de vue aeriennes sures, conformes et cinematographiques a chaque seance.',
        'Flexible Scheduling': 'Planification flexible',
        'Early mornings, evenings, and tight timelines - we adapt to your schedule to keep your projects moving.': 'Matins tot, soirees et delais serres - nous nous adaptons a votre emploi du temps pour faire avancer vos projets.',
        'Customized marketing solution': 'Solution marketing personnalisee',
        'Customized marketing strategies to meet the unique needs of each property.': 'Strategies marketing adaptees aux besoins uniques de chaque propriete.',
        '24-hour delivery time': 'Delai de livraison de 24 heures',
        'We understand your need for quick delivery times and our team will ensure your marketing materials are delivered as quickly as possible.': 'Nous comprenons votre besoin de rapidite et notre equipe livre vos contenus marketing le plus vite possible.',
        'Drone pilot with TC license': 'Pilote de drone avec licence TC',
        'All our pilots are licensed by Transport Canada and are fully insured.': 'Tous nos pilotes sont certifies par Transports Canada et entierement assures.',
        'Open 6 days a week': 'Ouvert 6 jours par semaine',
        'Open Monday to Saturday from 9am to 5pm.': 'Ouvert du lundi au samedi de 9 h a 17 h.',
        'Our offerings': 'Nos services',
        'Marketing-ready media across every step of the listing journey.': 'Des contenus prets au marketing a chaque etape du cycle de mise en marche.',
        'Marketing-Ready Media Across Every Step of the Listing Journey.': 'Des contenus prets au marketing a chaque etape du cycle de mise en marche.',
        'Professional Photography (HDR)': 'Photographie professionnelle (HDR)',
        'High-end interior & exterior photography for residential and commercial listings.': 'Photographie haut de gamme interieure et exterieure pour les inscriptions residentielles et commerciales.',
        'Cinematic Property Films': 'Films immobiliers cinematographiques',
        'High-production videos with storytelling, music, gimbal movement, and drone integration.': 'Videos de haute production avec narration, musique, mouvements stabilises et integration drone.',
        'Express Video Walkthroughs': 'Visites video express',
        'Clean, fast, MLS-friendly walkthroughs designed for speed, volume, and social use.': 'Visites claires, rapides et compatibles MLS, concues pour la rapidite, le volume et les reseaux sociaux.',
        'Drone Photography & Video': 'Photo et video par drone',
        'Aerial imagery and video to showcase location, scale, surroundings, and access points.': 'Images et videos aeriennes pour mettre en valeur l emplacement, l echelle, l environnement et les acces.',
        'Virtual Staging': 'Amenagement virtuel',
        'Digitally staged interiors to help buyers visualize scale, layout, and lifestyle.': 'Interieurs amenages numeriquement pour aider les acheteurs a visualiser l espace, la disposition et le style de vie.',
        'Day-to-Dusk': 'Jour vers crepuscule',
        'Twilight-style exterior imagery to enhance curb appeal and listing presence.': 'Visuels exterieurs style crepuscule pour renforcer l attrait et la presence de l inscription.',
        '3D Tours (Matterport) - coming soon': 'Visites 3D (Matterport) - bientot',
        'Immersive virtual tours allowing buyers and tenants to explore spaces remotely.': 'Visites virtuelles immersives permettant aux acheteurs et locataires d explorer les espaces a distance.',
        'Floor Plans (coming soon)': 'Plans d etage (bientot)',
        'Accurate 2D & 3D floor plans for listings, marketing materials, and commercial use.': 'Plans 2D et 3D precis pour inscriptions, materiel marketing et usage commercial.',
        'Social Media Content for Brokers': 'Contenu reseaux sociaux pour courtiers',
        'Short-form videos, reels, and visuals designed for Instagram, TikTok, and LinkedIn.': 'Videos courtes, reels et visuels concus pour Instagram, TikTok et LinkedIn.',
        'Photo Retouching & Media Enhancement': 'Retouche photo et amelioration media',
        'Professional post-production including color correction, object removal, and polish.': 'Post-production professionnelle incluant correction couleur, suppression d objets et finition.',
        'Service Examples': 'Exemples de services',
        'See exactly what each service delivers with real visual outputs.': 'Voyez exactement ce que chaque service livre avec des resultats visuels reels.',
        'Photo-to-Video': 'Photo vers video',
        'Photo-to-video content': 'Contenu photo-vers-video',
        'Turns still photos into social-ready reels': 'Transforme des photos fixes en reels prets pour les reseaux sociaux',
        'Built for listing launches and ads': 'Concu pour les lancements d inscriptions et les publicites',
        'Fast delivery with clean motion pacing': 'Livraison rapide avec un rythme de mouvement propre',
        'Before': 'Avant',
        'After': 'Apres',
        'Transforms empty spaces into furnished rooms': 'Transforme des espaces vides en pieces amenagees',
        'Helps buyers visualize layout and lifestyle': 'Aide les acheteurs a visualiser la disposition et le style de vie',
        'Aligned with listing style and target audience': 'Aligne avec le style de l inscription et le public cible',
        'Converts daytime exteriors into twilight mood': 'Convertit des exterieurs de jour en ambiance crepuscule',
        'Enhances curb appeal and premium perception': 'Renforce l attrait exterieur et la perception premium',
        'Creates stronger first-impression visuals': 'Cree des visuels de premiere impression plus forts',
        'Image editing / object removal': 'Retouche d image / suppression d objets',
        'Removes visual distractions and clutter': 'Supprime les distractions visuelles et le desordre',
        'Balances lighting, color, and composition': 'Equilibre l eclairage, la couleur et la composition',
        'Keeps final images polished and MLS-ready': 'Maintient des images finales soignees et pretes pour le MLS',
        'Residential and commercial portfolio': 'Portfolio residentiel et commercial',
        'Browse recent work by property type.': 'Parcourez les projets recents par type de propriete.',
        'Residential and Commercial Portfolio': 'Portfolio residentiel et commercial',
        'Browse Recent Work by Property Type': 'Parcourez les projets par type de propriete',
        'View Gallery': 'Voir la galerie',
        'Residential': 'Residentiel',
        'Commercial': 'Commercial',
        'Photo': 'Photo',
        'Video': 'Video',
        'Showcase a listing like a campaign': 'Mettez une inscription en valeur comme une campagne',
        'We combine photo, video, drone, and floor plans into a cohesive marketing kit that feels polished across MLS and social.': 'Nous combinons photo, video, drone et plans d etage dans un kit marketing coherent et soigne pour le MLS et les reseaux sociaux.',
        'All': 'Tous',
        'Photos': 'Photos',
        'Videos': 'Videos',
        'Drone': 'Drone',
        'Create your own custom plan': 'Creez votre forfait sur mesure',
        'Create Your Own Custom Plan': 'Creez votre forfait sur mesure',
        'Pick a ready package or build your own with total control.': 'Choisissez un forfait pret a l emploi ou creez le votre avec un controle total.',
        'Pick A Ready Package Or Build Your Own With Total Control.': 'Choisissez un forfait pret a l emploi ou creez le votre avec un controle total.',
        'Starting at': 'A partir de',
        'Showcase Ready': 'Pret a diffuser',
        'Essential': 'Essentiel',
        'Essentiel': 'Essentiel',
        'Essentiel $250': 'Essentiel 250 $',
        'Perfect for condos and standard listings.': 'Parfait pour les condos et inscriptions standards.',
        'Up to 30 HDR images': 'Jusqu a 30 images HDR',
        'Up to 25 HDR images': 'Jusqu a 25 images HDR',
        'Up to 10 drone images': 'Jusqu a 10 images drone',
        'Up to 7 drone images': 'Jusqu a 7 images drone',
        'HDR photography (30 photos)': 'Photographie HDR (30 photos)',
        '24h delivery': 'Livraison 24 h',
        'MLS-ready formatting': 'Formatage pret pour MLS',
        'Basic retouching': 'Retouche de base',
        'Select package': 'Choisir ce forfait',
        '$250': '250 $',
        'Most Popular': 'Le plus populaire',
        'Premium': 'Premium',
        'Signature': 'Signature',
        'Signature $350': 'Signature 350 $',
        'For listings that need stronger marketing.': 'Pour les inscriptions qui demandent un marketing plus fort.',
        'Video teaser (MLS + social)': 'Teaser video (MLS + social)',
        'HDR photography (20-25 photos) 24h': 'Photographie HDR (20-25 photos) 24 h',
        '$350': '350 $',
        'Prestige': 'Prestige',
        'Prestige $500': 'Prestige 500 $',
        'Full media coverage for premium properties.': 'Couverture media complete pour les proprietes haut de gamme.',
        'HDR photography (25-30 photos) 24h': 'Photographie HDR (25-30 photos) 24 h',
        'Drone photo': 'Photo drone',
        'Cinematic walkthrough video': 'Video de visite cinematographique',
        'Social reel cut + floor plan': 'Montage reel social + plan d etage',
        'Social media cut (reel format)': 'Montage reseaux sociaux (format reel)',
        'Floor plan': 'Plan d etage',
        '$500': '500 $',
        'A la carte': 'A la carte',
        'Custom Build': 'Creation sur mesure',
        'Create Your Own': 'Creez le votre',
        'Create Your Own Plan': 'Creez votre forfait',
        'Custom': 'Sur mesure',
        'Design a custom media mix that fits each listing and your brand goals.': 'Concevez un mix media sur mesure adapte a chaque inscription et a vos objectifs de marque.',
        'Mix photo, video, and drone': 'Mix photo, video et drone',
        'Virtual staging add-ons': 'Options d amenagement virtuel',
        'Day-to-dusk and object removal': 'Jour-vers-crepuscule et suppression d objets',
        'Flexible delivery timeline': 'Calendrier de livraison flexible',
        'Build My Plan': 'Construire mon forfait',
        'Add items to any package and customize delivery per listing.': 'Ajoutez des options a n importe quel forfait et personnalisez la livraison selon chaque inscription.',
        'Custom add-ons': 'Options personnalisees',
        'Priority edits': 'Retouches prioritaires',
        'Flexible delivery': 'Livraison flexible',
        'Mix & match': 'Personnalisez',
        'New': 'Nouveau',
        'Turn your listing photos into reels in 24h.': 'Transformez vos photos d inscriptions en reels en 24 h.',
        'AI photo-to-video that feels polished and premium. Send your photos, pick a style, and we deliver ready-to-post reels in a day.': 'Une conversion photo-vers-video IA au rendu soignee et premium. Envoyez vos photos, choisissez un style et nous livrons des reels prets a publier en un jour.',
        'Get started': 'Commencer',
        'Get Started': 'Commencer',
        'How it works': 'Comment ca marche',
        'Fast. Efficient. Professional.': 'Rapide. Efficace. Professionnel.',
        'From booking to delivery, every step is streamlined.': 'De la reservation a la livraison, chaque etape est optimisee.',
        'Broker Content': 'Contenu pour courtiers',
        'Built for brokers who want to lead with authority.': 'Concu pour les courtiers qui veulent affirmer leur autorite.',
        'Professional on-camera content helps you build trust faster, strengthen your personal brand, and position yourself as the go-to expert for every listing.': 'Un contenu video professionnel vous aide a gagner la confiance plus vite, renforcer votre marque personnelle et vous positionner comme expert de reference pour chaque inscription.',
        '01': '01',
        'Book your services': 'Reservez vos services',
        'Schedule in under five minutes with clear pricing.': 'Planifiez en moins de cinq minutes avec des prix clairs.',
        '02': '02',
        'Capture on site': 'Capture sur place',
        'Our crew handles photo, video, and drone in one visit.': 'Notre equipe gere photo, video et drone en une seule visite.',
        '03': '03',
        'Expert editing': 'Montage expert',
        'We enhance every image to a premium, consistent finish.': 'Nous optimisons chaque image avec une finition premium et constante.',
        '04': '04',
        'Prompt delivery': 'Livraison rapide',
        'Receive photos in 24-48h and video in 72h.': 'Recevez les photos en 24-48 h et la video en 72 h.',
        'Trusted by Leading Brokerages': 'Fait confiance par les principales agences',
        'Trusted by leading brands worldwide': 'Fait confiance par des marques de premier plan dans le monde',
        'Unlock Visual Brilliance': 'Liberez la brillance visuelle',
        'Fast turnarounds and polished visuals that make every listing stand out.': 'Des delais rapides et des visuels soignes qui font ressortir chaque inscription.',
        'Consistent quality across every shoot. Easy team to work with.': 'Une qualite constante a chaque mandat. Une equipe simple et agreable.',
        'Video and drone assets that elevate our brand presentation.': 'Des contenus video et drone qui elevent notre presentation de marque.',
        'Booking is simple, delivery is reliable, and results stay premium.': 'La reservation est simple, la livraison fiable et le resultat reste premium.',
        'Residential Brokerage': 'Agence residentielle',
        'Commercial Real Estate': 'Immobilier commercial',
        'Property Marketing Team': 'Equipe marketing immobilier',
        'Development Group': 'Groupe de developpement',
        'Previous testimonial': 'Temoignage precedent',
        'Next testimonial': 'Temoignage suivant',
        'Go to testimonial 1': 'Aller au temoignage 1',
        'Go to testimonial 2': 'Aller au temoignage 2',
        'Go to testimonial 3': 'Aller au temoignage 3',
        'Go to testimonial 4': 'Aller au temoignage 4',
        'Previous slide': 'Diapositive precedente',
        'Next slide': 'Diapositive suivante',
        '"Maccento delivers consistent, fast media that helps our listings stand out."': '"Maccento livre un contenu rapide et constant qui fait ressortir nos inscriptions."',
        '"Reliable turnaround and professional edits © great partner for our team."': '"Delais fiables et retouches professionnelles, excellent partenaire pour notre equipe."',
        '"Their video and drone work elevate our marketing assets."': '"Leur travail video et drone eleve la qualite de nos contenus marketing."',
        '"Easy booking and consistent quality across all jobs."': '"Reservation simple et qualite constante sur tous les mandats."',
        'Let\'s create something that sets your listing apart.': 'Creons quelque chose qui distingue votre inscription.',
        'Let\'s Create Something that Sets Your Listing Apart': 'Creons quelque chose qui distingue votre inscription',
        'From premium real estate media to strategic branding content and lead-focused visuals, Maccento Real Estate Media delivers refined, high-impact content designed to elevate your image and position your properties at their best.': 'Des medias immobiliers haut de gamme au contenu de marque strategique et aux visuels axes sur les prospects, Maccento Real Estate Media livre un contenu raffine et percutant pour elever votre image et positionner vos proprietes a leur meilleur.',
        'Contact our sales team': 'Contacter notre equipe commerciale',
        'Request a quote': 'Demander un devis',
        'Please provide your information and share details about your needs.': 'Veuillez fournir vos informations et decrire vos besoins.',
        'Full Name': 'Nom complet',
        'Agency or Company?': 'Agence ou entreprise?',
        'Phone': 'Telephone',
        'E-mail': 'Courriel',
        'Services required?': 'Services requis?',
        'Photography': 'Photographie',
        'Videography': 'Videographie',
        'Drone': 'Drone',
        '3D': '3D',
        'Virtual staging': 'Amenagement virtuel',
        'Other services': 'Autres services',
        'Region': 'Region',
        'Montreal': 'Montreal',
        'Laval': 'Laval',
        'South Shore': 'Rive-Sud',
        'North Shore': 'Rive-Nord',
        'Other': 'Autre',
        'Message': 'Message',
        'Send': 'Envoyer',
        'Contact Us Today!': 'Contactez-nous aujourd hui',
        'Included': 'Inclus',
        'These perks are included with all our photography services, for a difference you can see.': 'Ces avantages sont inclus avec tous nos services de photographie, pour une difference visible.',
        'Window masking': 'Masquage des fenetres',
        'Sky replacement': 'Remplacement du ciel',
        'Image on TV screen': 'Image sur ecran TV',
        'Fire in fireplace': 'Feu dans la cheminee',
        'Personal image blurring': 'Floutage des elements personnels',
        'Resized for social media and Centris': 'Redimensionne pour les reseaux sociaux et Centris',
        'One-stop real estate content studio serving brokers across Montreal and beyond.': 'Studio de contenu immobilier tout-en-un au service des courtiers de Montreal et d ailleurs.',
        'Client': 'Client',
        'Client Login': 'Connexion client',
        'Client Portal': 'Portail client',
        'Terms': 'Conditions',
        'Montreal, QC': 'Montreal, QC',
        '© 2026 Maccento Real Estate Media': '© 2026 Maccento Media Immobilier',
        'Open menu': 'Ouvrir le menu',
        'Drag to compare before and after': 'Glissez pour comparer avant et apres',
        'Toggle service details': 'Afficher/masquer les details du service',
        'Language toggle': 'Basculer la langue',
        'Social media': 'Reseaux sociaux',
        'Instagram': 'Instagram',
        'LinkedIn': 'LinkedIn',
        'Facebook': 'Facebook',
        'YouTube': 'YouTube'
      };

      const normal = (value) => value.replace(/\s+/g, ' ').trim();
      const normalizeKey = (value) => normal(String(value || '')).toLowerCase();
      const frTranslationsLower = Object.fromEntries(
        Object.entries(frTranslations).map(([key, value]) => [normalizeKey(key), value])
      );
      const getFrTranslation = (value) => {
        const exact = frTranslations[value];
        if (exact) return exact;
        return frTranslationsLower[normalizeKey(value)] || null;
      };
      const getCookieLang = () => {
        const match = document.cookie.match(/(?:^|;\\s*)site_lang=(en|fr)(?:;|$)/i);
        return match ? match[1].toLowerCase() : null;
      };
      const setCookieLang = (lang) => {
        document.cookie = `site_lang=${lang}; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
      };
      const textNodes = [];
      const placeholderElements = Array.from(document.querySelectorAll('input[placeholder], textarea[placeholder]'));
      const ariaElements = Array.from(document.querySelectorAll('[aria-label]'));
      const titleEl = document.querySelector('title');
      const root = document.documentElement;
      const langButtons = Array.from(document.querySelectorAll('.lang-btn'));
      const animatedHeadings = Array.from(
        document.querySelectorAll('.section-title:not(.portfolio-page-heading), .contact-title, .home-portfolio-heading')
      );

      const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
          if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
          const parent = node.parentElement;
          if (!parent) return NodeFilter.FILTER_REJECT;
          const tag = parent.tagName;
          if (tag === 'SCRIPT' || tag === 'STYLE') return NodeFilter.FILTER_REJECT;
          return NodeFilter.FILTER_ACCEPT;
        }
      });

      let current;
      while ((current = walker.nextNode())) {
        textNodes.push({
          node: current,
          original: current.nodeValue,
          trimmed: normal(current.nodeValue)
        });
      }

      placeholderElements.forEach((el) => {
        el.dataset.placeholderOriginal = el.getAttribute('placeholder') || '';
      });
      ariaElements.forEach((el) => {
        el.dataset.ariaOriginal = el.getAttribute('aria-label') || '';
      });
      const originalTitle = titleEl ? titleEl.textContent : '';

      const rebuildHeadingWordAnimation = () => {
        const WORD_STAGGER_MS = 180;
        const WORD_STAGGER_CAP_MS = 2600;
        animatedHeadings.forEach((el) => {
          el.classList.remove('heading-word-animate');
          if (!el.dataset.headingColorOriginal) {
            el.dataset.headingColorOriginal = getComputedStyle(el).color;
          }
          const existingOverlays = el.querySelectorAll('.heading-word-overlay');
          existingOverlays.forEach((node) => node.remove());

          const directText = Array.from(el.childNodes)
            .filter((node) => node.nodeType === Node.TEXT_NODE)
            .map((node) => node.nodeValue || '')
            .join('');

          const rawHeadingText = directText || el.textContent || '';
          const headingText = normal(rawHeadingText);
          if (!headingText) return;

          const overlay = document.createElement('span');
          overlay.className = 'heading-word-overlay';
          overlay.setAttribute('aria-hidden', 'true');
          overlay.style.color = el.dataset.headingColorOriginal || getComputedStyle(el).color;

          const isAboutTitle = el.classList.contains('about-title');
          const lines = isAboutTitle
            ? rawHeadingText
                .replace(/\r/g, '')
                .split('\n')
                .map((line) => normal(line))
                .filter(Boolean)
            : [headingText];

          let wordIndex = 0;
          lines.forEach((line, lineIndex) => {
            const words = line.split(' ').filter(Boolean);
            words.forEach((word, index) => {
              const wordEl = document.createElement('span');
              wordEl.className = 'heading-word';
              wordEl.textContent = word;
              wordEl.style.setProperty('--word-delay', `${Math.min(wordIndex * WORD_STAGGER_MS, WORD_STAGGER_CAP_MS)}ms`);
              overlay.appendChild(wordEl);
              wordIndex += 1;

              if (index < words.length - 1) {
                overlay.appendChild(document.createTextNode(' '));
              }
            });

            if (lineIndex < lines.length - 1) {
              overlay.appendChild(document.createElement('br'));
            }
          });

          el.classList.add('heading-word-animate');
          el.appendChild(overlay);
        });
      };

      const applyLanguage = (lang) => {
        const isFr = lang === 'fr';
        textNodes.forEach((entry) => {
          if (!isFr) {
            entry.node.nodeValue = entry.original;
            return;
          }
          const translated = getFrTranslation(entry.trimmed);
          if (!translated) {
            entry.node.nodeValue = entry.original;
            return;
          }
          const leading = entry.original.match(/^\s*/)[0];
          const trailing = entry.original.match(/\s*$/)[0];
          entry.node.nodeValue = leading + translated + trailing;
        });

        placeholderElements.forEach((el) => {
          const original = el.dataset.placeholderOriginal || '';
          const translated = getFrTranslation(original);
          el.setAttribute('placeholder', isFr && translated ? translated : original);
        });

        ariaElements.forEach((el) => {
          const original = el.dataset.ariaOriginal || '';
          const translated = getFrTranslation(original);
          el.setAttribute('aria-label', isFr && translated ? translated : original);
        });

        if (titleEl) {
          const translatedTitle = getFrTranslation(originalTitle);
          titleEl.textContent = isFr && translatedTitle ? translatedTitle : originalTitle;
        }

        root.setAttribute('lang', isFr ? 'fr' : 'en');

        langButtons.forEach((btn) => {
          const code = (btn.dataset.lang || btn.textContent || '').trim().toLowerCase();
          const active = code === lang;
          btn.classList.toggle('active', active);
          btn.setAttribute('aria-pressed', String(active));
        });

        try {
          localStorage.setItem('site-lang', lang);
        } catch (e) {
        }
        setCookieLang(lang);

        rebuildHeadingWordAnimation();
      };

      langButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          const code = (btn.dataset.lang || btn.textContent || '').trim().toLowerCase();
          if (code === 'en' || code === 'fr') {
            applyLanguage(code);
          }
        });
      });

      let initialLang = 'en';
      try {
        const queryLang = new URLSearchParams(window.location.search).get('lang');
        if (queryLang === 'fr' || queryLang === 'en') {
          initialLang = queryLang;
        }
      } catch (e) {
      }
      if (initialLang === 'en') {
        const cookieLang = getCookieLang();
        if (cookieLang === 'fr' || cookieLang === 'en') {
          initialLang = cookieLang;
        }
      }
      try {
        const stored = localStorage.getItem('site-lang');
        if (stored === 'fr' || stored === 'en') {
          initialLang = stored;
        }
      } catch (e) {
      }
      applyLanguage(initialLang);
    })();
  </script>
</body>
</html>
