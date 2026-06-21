<?php
/* ============================================================
   HKSA WEBSITE — index-release.php
   Stanford Hong Kong Student Association
   https://hksa.stanford.edu/

   DEPLOY: rename to index.php before uploading to cPanel.
   DATA:   reads from private/hksa/ (outside web root).
   PATH:   __DIR__ = /home/hohanson/hksa.stanford.edu/
           /../   = /home/hohanson/
   ============================================================ */

/* --- safe JSON loader: returns [] on any failure, leaks nothing --- */
function load_json(string $path): array {
    if (!file_exists($path))   return [];
    if (!is_readable($path))   return [];
    $raw = file_get_contents($path);
    if ($raw === false)        return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$base     = __DIR__ . '/../private/hksa/';
$events   = load_json($base . 'events.json');
$people   = load_json($base . 'people.json');
$links    = load_json($base . 'links.json');

$current_officers = $people['current']['officers'] ?? [];
$past_years       = $people['past'] ?? [];

/* --- helpers --- */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function link_or_text(string $label, ?string $url): string {
    if ($url) {
        return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($label) . '</a>';
    }
    return e($label);
}

/* --- ghost card count for carousel last page --- */
$PER_PAGE = 6;
$rem = count($events) % $PER_PAGE;
$ghost_count = ($rem === 0) ? 0 : $PER_PAGE - $rem;

/* --- affiliated orgs config (label => links.json key) --- */
$affiliated = [
    'A3C'   => 'a3c',
    'AASA'  => 'aasa',
    'AAGSA' => 'aagsa',
    'TCS'   => 'tcs',
    'MSA'   => 'msa',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stanford HKSA</title>
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <meta property="og:image" content="assets/og-image.jpg" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&family=Noto+Serif+TC:wght@400;700&family=Noto+Sans+TC:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --red:          #B1040E;
      --black:        #2E2D29;
      --bg:           #F5F5F0;
      --white:        #FFFFFF;
      --grey:         #53565A;
      --border:       #D5D5D4;
      --font-display: 'Playfair Display', 'Noto Serif TC', serif;
      --font-body:    'Inter', 'Noto Sans TC', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; scroll-snap-type: y mandatory; }
    html.no-snap { scroll-snap-type: none; }

    @media (max-width: 600px) {
      html { scroll-snap-type: none; }
    }

    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--black);
      font-size: 16px;
      line-height: 1.6;
    }

    /* NAV */
    nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
      z-index: 100;
      padding: 0 2rem;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .nav-logo {
      font-family: var(--font-display);
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--black);
      text-decoration: none;
      letter-spacing: 0.02em;
    }

    .nav-logo span { color: var(--red); }

    .nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
    }

    .nav-links a {
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--grey);
      text-decoration: none;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      transition: color 0.2s;
    }

    .nav-links a:hover { color: var(--red); }

    /* HERO */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 80px 2rem 4rem;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('assets/bg-hero.jpg');
      background-size: cover;
      opacity: 0.15;
      z-index: 0;
    }

    /* SECTION WRAPS — full-bleed background image containers */
    .section-wrap {
      position: relative;
      overflow: hidden;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    .section-wrap::before {
      content: '';
      position: absolute;
      inset: 0;
      background-size: cover;
      background-attachment: fixed;
      opacity: 0.15;
      z-index: 0;
    }

    /* adjust background-position per photo: top / center / bottom / "center 30%" etc. */
    .hero::before                  { background-position: center; }
    .section-wrap--about::before   { background-image: url('assets/bg-about.jpg');  background-position: center; }
    .section-wrap--events::before  { background-image: url('assets/bg-events.jpg'); background-position: top; }
    .section-wrap--people::before  { background-image: url('assets/bg-people.jpg'); background-position: center; }
    .section-wrap--join::before    { background-image: url('assets/bg-join.jpg');   background-position: center; }

    /* scroll-snap alignment — full-height sections only */
    .hero                  { scroll-snap-align: start; }
    .section-wrap--about   { scroll-snap-align: start; }
    .section-wrap--events  { scroll-snap-align: start; }
    .section-wrap--people  { scroll-snap-align: start; }
    .section-wrap--join    { scroll-snap-align: start; }

    .section-wrap section {
      position: relative;
      z-index: 1;
      width: 100%;
    }

    .hero-inner {
      position: relative;
      z-index: 1;
      max-width: 1100px;
      margin: 0 auto;
      width: 100%;
    }

    .hero-heading {
      display: flex;
      align-items: stretch;
      gap: 3rem;
      margin-bottom: 1.2rem;
    }

    .hero-bar {
      width: 5px;
      background: var(--red);
      flex-shrink: 0;
      align-self: stretch;
    }

    .hero-text h1 {
      font-family: var(--font-display);
      font-size: clamp(2.8rem, 6vw, 5rem);
      font-weight: 700;
      line-height: 1.1;
      color: var(--black);
      margin-bottom: 1.2rem;
    }

    .hero-text h1 em {
      font-style: normal;
      color: var(--red);
    }

    .hero-text p {
      font-size: 1.1rem;
      color: var(--grey);
      font-weight: 300;
      max-width: 480px;
      margin-bottom: 2rem;
      line-height: 1.8;
    }

    .hero-cta {
      display: inline-block;
      background: var(--red);
      color: var(--white);
      padding: 0.75rem 2rem;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      transition: opacity 0.2s;
    }

    .hero-cta:hover { opacity: 0.85; }

    /* SECTIONS */
    section {
      padding: 5rem 2rem;
      max-width: 1100px;
      margin: 0 auto;
    }

    .section-label {
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--red);
      margin-bottom: 1rem;
    }

    .section-title {
      font-family: var(--font-display);
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 700;
      color: var(--black);
      margin-bottom: 1.2rem;
    }

    .section-body {
      font-size: 1rem;
      color: var(--grey);
      max-width: 580px;
      line-height: 1.9;
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      max-width: 1100px;
      margin: 0 auto;
    }

    /* CARDS (shared by events and people) */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
      margin-top: 2.5rem;
    }

    .card {
      background: var(--white);
      padding: 1.8rem;
      border: 1px solid var(--border);
      transition: border-color 0.2s;
      min-width: 0;
    }

    .card:hover { border-color: var(--red); }

    .card-tag {
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--red);
      margin-bottom: 0.6rem;
    }

    .card h3 {
      font-family: var(--font-display);
      font-size: 1.2rem;
      color: var(--black);
      margin-bottom: 0.5rem;
    }

    .card p {
      font-size: 0.88rem;
      color: var(--grey);
      line-height: 1.7;
    }

    /* OFFICER PHOTO */
    .officer-photo {
      width: 100%;
      height: auto;
      display: block;
      margin-bottom: 1.2rem;
      background: var(--bg);
    }

    /* IMAGE DOWNLOAD PREVENTION */
    img { user-select: none; -webkit-user-drag: none; }

    /* PAST OFFICERS TOGGLE */
    .past-toggle {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1.8rem;
      background: none;
      border: 1px solid var(--border);
      color: var(--grey);
      font-family: var(--font-body);
      font-size: 0.85rem;
      font-weight: 500;
      letter-spacing: 0.04em;
      padding: 0.55rem 1.2rem;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }

    .past-toggle:hover { border-color: var(--red); color: var(--red); }

    .past-toggle .toggle-chevron {
      display: inline-block;
      transition: transform 0.25s;
      font-style: normal;
      font-size: 0.75rem;
    }

    .past-toggle.open .toggle-chevron { transform: rotate(180deg); }

    .past-officers {
      overflow: hidden;
      max-height: 0;
      transition: max-height 0.35s ease;
    }

    .past-officers.open { max-height: 2000px; }

    .past-year-label {
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--grey);
      margin: 2rem 0 1rem;
    }

    .exec-row {
      display: flex;
      align-items: baseline;
      gap: 0.75rem;
      margin-top: 1rem;
      padding-top: 0.8rem;
      border-top: 1px solid var(--border);
    }

    .exec-names {
      font-size: 0.88rem;
      color: var(--grey);
    }

    .exec-row .card-tag {
      flex-shrink: 0;
      width: 120px;
      min-width: 120px;
      margin-bottom: 0;
    }

    /* JOIN */
    .join-block {
      padding: 4rem 2rem;
      text-align: center;
    }

    .join-block .section-title { margin-bottom: 1rem; }
    .join-block p { margin-bottom: 2rem; }

    .join-cta {
      display: inline-block;
      background: var(--red);
      color: var(--white);
      border: 2px solid var(--red);
      padding: 0.75rem 2rem;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      transition: background 0.2s, color 0.2s;
      min-width: 160px;
      text-align: center;
    }

    .join-cta:hover { background: var(--white); color: var(--red); }
    .join-buttons { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
    .join-buttons .join-cta { flex: 1 0 auto; max-width: 200px; }

    /* CONTACT / FOOTER */
    footer {
      background: var(--black);
      border-top: 1px solid var(--grey);
      padding: 5rem 2rem 3rem;
      text-align: center;
      scroll-snap-align: start;
    }

    .contact-inner {
      max-width: 560px;
      margin: 0 auto;
    }

    footer .section-label { color: #E50808; }
    footer .section-title { color: var(--white); margin-bottom: 1rem; }
    footer .section-body  { color: var(--border); margin-bottom: 2.5rem; }

    .contact-form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      text-align: left;
    }

    .contact-form input,
    .contact-form textarea {
      width: 100%;
      background: transparent;
      border: 1px solid var(--grey);
      color: var(--white);
      font-family: var(--font-body);
      font-size: 0.9rem;
      padding: 0.75rem 1rem;
      outline: none;
      transition: border-color 0.2s;
    }

    .contact-form input::placeholder,
    .contact-form textarea::placeholder { color: var(--grey); }

    .contact-form input:focus,
    .contact-form textarea:focus { border-color: var(--white); }

    .contact-form textarea { min-height: 130px; resize: none; }

    .contact-submit {
      align-self: flex-start;
      background: var(--red);
      color: var(--white);
      border: 2px solid var(--red);
      padding: 0.75rem 2rem;
      font-family: var(--font-body);
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }

    .contact-submit:hover { background: var(--white); color: var(--red); }
    .contact-submit:disabled { opacity: 0.5; cursor: not-allowed; }

    .contact-feedback {
      font-size: 0.88rem;
      margin-top: 0.5rem;
      min-height: 1.2em;
    }

    .contact-feedback.success { color: #008566; }
    .contact-feedback.error   { color: var(--red); }

    /* honeypot — visually hidden */
    .contact-hp { display: none; }

    .contact-subject-wrap {
      position: relative;
    }

    .contact-subject-wrap input {
      padding-right: 3rem;
    }

    .contact-counter {
      position: absolute;
      right: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.75rem;
      color: var(--grey);
      pointer-events: none;
      transition: color 0.2s;
    }

    .contact-counter.warn { color: var(--red); }

    .footer-meta {
      margin-top: 3rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--grey);
    }

    .footer-meta p { font-size: 0.8rem; color: var(--border); }
    .footer-meta a { color: var(--border); text-decoration: none; transition: color 0.2s; }
    .footer-meta a:hover { color: var(--red); }

    /* EVENTS CAROUSEL - desktop */
    .carousel-wrap {
      position: relative;
      margin-top: 2.5rem;
    }

    .carousel-arr {
      width: 36px;
      height: 36px;
      border-radius: 0;
      border: 1px solid var(--border);
      background: var(--white);
      color: var(--black);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.1rem;
      transition: border-color 0.2s, color 0.2s;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
    }

    .carousel-arr:hover { border-color: var(--red); color: var(--red); }
    .carousel-arr[hidden] { display: none; }

    #evtPrev { left: -52px; }
    #evtNext { right: -52px; }

    .carousel-viewport {
      width: 100%;
      overflow: hidden;
    }

    /* --- easy to change layout: adjust grid-template here --- */
    .carousel-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(2, auto);
      gap: 1.5rem;
    }

    .evt-expand-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
      background: none;
      border: 1px solid var(--border);
      color: var(--grey);
      font-family: var(--font-body);
      font-size: 0.85rem;
      font-weight: 500;
      letter-spacing: 0.04em;
      padding: 0.55rem 1.2rem;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }

    .evt-expand-btn:hover { border-color: var(--red); color: var(--red); }

    .card-ghost {
      visibility: hidden;
      pointer-events: none;
    }

    /* PAGE INDICATOR DOTS */
    .carousel-dots {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      margin-top: 1rem;
    }

    .carousel-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--border);
      border: none;
      padding: 0;
      cursor: pointer;
      transition: background 0.2s;
    }

    .carousel-dot.active { background: var(--red); }

    /* ACTIVE NAV HIGHLIGHT */
    .nav-links a.nav-active { color: var(--red); }

    /* RESPONSIVE - all mobile rules consolidated here */
    @media (max-width: 600px) {
      .nav-links { display: none; }
      .hero-heading { gap: 1.2rem; flex-direction: column; }
      .hero-bar { width: 40px; height: 5px; align-self: auto; }
      .hero-text > div[style] { padding-left: 0 !important; }
      .card-grid { grid-template-columns: 1fr; }
      .evt-expand-btn { display: none; }
      .carousel-dots { display: none; }
      .join-buttons { flex-direction: column; align-items: center; }
      .join-cta { width: 240px; }
      .carousel-wrap { gap: 0; }
      .carousel-arr { display: none; }
      .carousel-viewport {
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
      }
      .carousel-viewport::-webkit-scrollbar { display: none; }
      .carousel-grid {
        display: grid;
        grid-auto-flow: column;
        grid-template-rows: repeat(3, auto);
        grid-template-columns: unset;
        width: max-content;
        gap: 1rem;
      }
      .carousel-grid .card {
        width: 75vw;
        scroll-snap-align: start;
      }
      .exec-row .card-tag {
        width: 80px;
        min-width: 80px;
      }
      .hero::before,
      .section-wrap::before { background-attachment: scroll; }
    }
  </style>

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-TLH5792XHT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-TLH5792XHT');
  </script>
</head>
<body>

  <!-- NAV -->
  <nav>
    <a href="#" class="nav-logo">Stanford <span>HKSA</span></a>
    <ul class="nav-links">
      <li><a href="#about">About</a></li>
      <li><a href="#events">Events</a></li>
      <li><a href="#people">People</a></li>
      <li><a href="#join">Join</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
  </nav>

  <!-- HERO -->
  <div class="hero">
    <div class="hero-inner">
      <div class="hero-text">
        <div class="hero-heading">
          <div class="hero-bar"></div>
          <div>
            <p style="font-family:var(--font-display);font-size:clamp(1.8rem,3vw,2.6rem);font-weight:700;color:var(--black);letter-spacing:0.08em;margin-bottom:0.6rem;">史丹福大學 香港學生會</p>
            <h1>Hong Kong Student Association at <em>Stanford.</em></h1>
          </div>
        </div>
        <div style="padding-left: calc(5px + 3rem);">
          <p>A home away from home for Hong Kong students, friends, and anyone who enjoys our culture.</p>
          <a href="#join" class="hero-cta">Get Involved</a>
        </div>
      </div>
    </div>
  </div>

  <hr class="divider" />

  <!-- ABOUT -->
  <div class="section-wrap section-wrap--about" id="about">
  <section>
    <div class="section-label">Who We Are</div>
    <h2 class="section-title">A Hong Kong community at Stanford.</h2>
    <p class="section-body">We are a group of undergraduate and graduate students dedicated to preserving and promoting Hong Kong culture at Stanford, and connecting students passionate about the culture.</p>
  </section>
  </div>

  <hr class="divider" />

  <!-- EVENTS -->
  <div class="section-wrap section-wrap--events" id="events">
  <section>
    <div class="section-label">What We Do</div>
    <h2 class="section-title">Events throughout the year.</h2>
    <p class="section-body">From food events and mahjong nights to karaoke and movie screenings, we bring Hong Kong culture to life on campus. There&rsquo;s always something on.</p>
    <div class="carousel-wrap" id="eventsCarousel">
      <button class="carousel-arr" id="evtPrev" onclick="evtMove(-1)" aria-label="Previous" hidden>&#8249;</button>
      <div class="carousel-viewport">
        <div class="carousel-grid" id="evtGrid">
          <?php foreach ($events as $ev): ?>
          <div class="card">
            <div class="card-tag"><?= e($ev['tag']) ?></div>
            <h3><?= e($ev['name']) ?></h3>
            <p><?= e($ev['description']) ?></p>
          </div>
          <?php endforeach; ?>
          <?php for ($g = 0; $g < $ghost_count; $g++): ?>
          <div class="card card-ghost" aria-hidden="true"></div>
          <?php endfor; ?>
        </div>
      </div>
      <button class="carousel-arr" id="evtNext" onclick="evtMove(1)" aria-label="Next">&#8250;</button>
    </div>
    <div class="carousel-dots" id="evtDots"></div>
    <button class="evt-expand-btn" id="evtExpandBtn" onclick="evtToggleExpand()">Show all events &#9660;</button>
  </section>
  </div>

  <hr class="divider" />

  <!-- PEOPLE -->
  <div class="section-wrap section-wrap--people" id="people">
  <section>
    <div class="section-label">Our Team</div>
    <h2 class="section-title">Student leaders.</h2>
    <p class="section-body">Meet the officers running HKSA this year.</p>

    <div class="card-grid">
      <?php foreach ($current_officers as $o): ?>
      <div class="card">
        <img src="<?= e($o['headshot']) ?>" alt="<?= e($o['name']) ?>" class="officer-photo" width="250" height="250" />
        <div class="card-tag"><?= e($o['role']) ?></div>
        <h3><?= e($o['name']) ?></h3>
        <?php if (!empty($o['bio'])): ?><p><?= e($o['bio']) ?></p><?php endif; ?>
        <p><a href="mailto:<?= e($o['email'][0]) ?>" style="color:var(--red);font-size:0.85rem;"><?= e($o['email'][0]) ?></a></p>
      </div>
      <?php endforeach; ?>
    </div>

    <button class="past-toggle" id="pastToggle" onclick="
      this.classList.toggle('open');
      document.getElementById('pastOfficers').classList.toggle('open');
      this.querySelector('.toggle-label').textContent = this.classList.contains('open') ? 'Hide past officers' : 'Show past officers';
      this.setAttribute('aria-expanded', this.classList.contains('open') ? 'true' : 'false');
    " aria-expanded="false">
      <span class="toggle-label">Show past officers</span>
      <i class="toggle-chevron">&#9660;</i>
    </button>

    <div class="past-officers" id="pastOfficers">
      <?php foreach ($past_years as $yr): ?>
      <div class="past-year-label"><?= e(str_replace('-', "\u{2013}", $yr['year'])) ?></div>
      <div class="card-grid">
        <?php foreach ($yr['officers'] as $o): ?>
        <div class="card">
          <div class="card-tag"><?= e($o['role']) ?></div>
          <h3><?= e($o['name']) ?></h3>
          <?php /* emails stored in JSON but deliberately not rendered */ ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($yr['exec_team'])): ?>
      <div class="exec-row">
        <span class="card-tag" style="margin-bottom:0;">Executive Team</span>
        <span class="exec-names"><?= e(implode(' · ', $yr['exec_team'])) ?></span>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>
  </div>

  <hr class="divider" />

  <!-- JOIN -->
  <div class="section-wrap section-wrap--join" id="join">
  <section>
    <div class="join-block">
      <div class="section-label">Join Us</div>
      <h2 class="section-title">Get Involved.</h2>
      <p>Sign up on CardinalEngage to become an official member, and join our mailing list and follow us on Instagram to get the latest updates.</p>
      <div class="join-buttons">
        <a href="<?= e($links['cardinal_engage'] ?? '#') ?>" class="join-cta" target="_blank" rel="noopener noreferrer">CardinalEngage</a>
        <a href="<?= e($links['mailing_list'] ?? '#') ?>" class="join-cta" target="_blank" rel="noopener noreferrer">Mailing List</a>
        <a href="<?= e($links['instagram'] ?? '#') ?>" class="join-cta" target="_blank" rel="noopener noreferrer">Instagram</a>
      </div>
    </div>
  </section>
  </div>

  <!-- CONTACT / FOOTER -->
  <footer id="contact">
    <div class="contact-inner">
      <div class="section-label">Contact</div>
      <h2 class="section-title">Get in touch.</h2>
      <p class="section-body">Have a question or want to know more about HKSA? Send us a message and we&rsquo;ll get back to you.</p>

      <form class="contact-form" id="contactForm">
        <input type="text"  name="name"    id="contactName"    placeholder="Name"          autocomplete="name"  required />
        <input type="email" name="email"   id="contactEmail"   placeholder="Email address" autocomplete="email" required />
        <div class="contact-subject-wrap">
          <input type="text" name="subject" id="contactSubject" placeholder="Subject" maxlength="78" required />
          <span class="contact-counter" id="subjectCounter">78</span>
        </div>
        <textarea           name="message" id="contactMessage" placeholder="Message"        required></textarea>
        <!-- honeypot -->
        <div class="contact-hp" aria-hidden="true">
          <input type="text" name="website" tabindex="-1" autocomplete="off" />
        </div>
        <button type="submit" class="contact-submit" id="contactSubmit">Send Message</button>
        <div class="contact-feedback" id="contactFeedback" aria-live="polite"></div>
      </form>

      <div class="footer-meta">
        <p>&copy; 2026&ndash;2027 Stanford Hong Kong Student Association &middot; Last updated June 2026</p>
        <p style="margin-top: 0.4rem;">Affiliated with:
          <?php
          $parts = [];
          foreach ($affiliated as $label => $key) {
              $url = $links[$key] ?? null;
              $parts[] = link_or_text($label, $url);
          }
          echo implode(' &middot; ', $parts);
          ?>
        </p>
      </div>
    </div>
  </footer>

  <script>
    /* EVENTS CAROUSEL
       Cards are server-rendered and in the DOM at load time.
       To change layout: update PER_PAGE + CSS grid-template-columns/rows to match. */
    (function() {
      var PER_PAGE = 6; /* 3 cols x 2 rows -- must match PHP $PER_PAGE and CSS grid */
      var MOBILE_BP = 600;
      var page = 0;
      var expanded = false;
      var allCards = document.querySelectorAll('#evtGrid .card');
      var cards = Array.prototype.filter.call(allCards, function(c) {
        return !c.classList.contains('card-ghost');
      });
      var ghosts = Array.prototype.filter.call(allCards, function(c) {
        return c.classList.contains('card-ghost');
      });
      var total = cards.length;
      var maxPage = Math.max(0, Math.ceil(total / PER_PAGE) - 1);
      var prev = document.getElementById('evtPrev');
      var next = document.getElementById('evtNext');
      var expandBtn = document.getElementById('evtExpandBtn');
      var dotsContainer = document.getElementById('evtDots');
      var dots = [];

      function isMobile() { return window.innerWidth <= MOBILE_BP; }

      function buildDots() {
        dotsContainer.innerHTML = '';
        dots = [];
        for (var d = 0; d <= maxPage; d++) {
          var dot = document.createElement('button');
          dot.className = 'carousel-dot';
          dot.setAttribute('aria-label', 'Page ' + (d + 1));
          (function(idx) { dot.onclick = function() { if (!expanded) show(idx); }; })(d);
          dotsContainer.appendChild(dot);
          dots.push(dot);
        }
      }

      function updateDots(p) {
        dots.forEach(function(dot, i) {
          dot.classList.toggle('active', i === p);
        });
        dotsContainer.style.display = expanded ? 'none' : '';
      }

      function equalizeHeights() {
        cards.forEach(function(c) { c.style.display = ''; c.style.minHeight = ''; });
        var maxH = 0;
        cards.forEach(function(c) { var h = c.offsetHeight; if (h > maxH) maxH = h; });
        cards.forEach(function(c) { c.style.minHeight = maxH + 'px'; });
        ghosts.forEach(function(c) { c.style.minHeight = maxH + 'px'; });
      }

      function showAll() {
        cards.forEach(function(c) { c.style.display = ''; });
        ghosts.forEach(function(c) { c.style.display = 'none'; });
        prev.hidden = true;
        next.hidden = true;
        dotsContainer.style.display = 'none';
      }

      function show(p) {
        page = p;
        var start = page * PER_PAGE;
        var end = start + PER_PAGE;
        cards.forEach(function(c, i) {
          c.style.display = (i >= start && i < end) ? '' : 'none';
        });
        var realOnPage = Math.min(end, total) - start;
        var ghostsNeeded = PER_PAGE - realOnPage;
        ghosts.forEach(function(c, i) {
          c.style.display = (page === maxPage && i < ghostsNeeded) ? '' : 'none';
        });
        prev.hidden = (page === 0);
        next.hidden = (page === maxPage);
        updateDots(page);
      }

      function init() {
        buildDots();
        if (isMobile()) {
          showAll();
        } else {
          equalizeHeights();
          show(0);
        }
      }

      window.evtMove = function(dir) { if (!isMobile() && !expanded) show(page + dir); };

      window.evtToggleExpand = function() {
        expanded = !expanded;
        if (expanded) {
          showAll();
          expandBtn.innerHTML = 'Show fewer &#9650;';
          document.documentElement.classList.add('no-snap');
        } else {
          equalizeHeights();
          show(0);
          expandBtn.innerHTML = 'Show all events &#9660;';
          document.documentElement.style.scrollBehavior = 'auto';
          document.getElementById('events').scrollIntoView();
          document.documentElement.classList.remove('no-snap');
          document.documentElement.style.scrollBehavior = '';
        }
      };

      window.addEventListener('load', init);
      window.addEventListener('resize', function() { page = 0; expanded = false; init(); });
    })();

    /* IMAGE DOWNLOAD PREVENTION (casual deterrent -- not cryptographic)
       Blocks right-click, drag, and common save/source/print shortcuts on all images. */
    (function() {
      document.querySelectorAll('img').forEach(function(img) {
        img.setAttribute('draggable', 'false');
      });

      document.addEventListener('contextmenu', function(e) {
        if (e.target.closest('img')) e.preventDefault();
      });

      document.addEventListener('dragstart', function(e) {
        if (e.target.closest('img')) e.preventDefault();
      });

      document.addEventListener('touchstart', function(e) {
        if (e.target.closest('img')) e.preventDefault();
      }, { passive: false });

      document.addEventListener('keydown', function(e) {
        var key = e.key.toLowerCase();
        if ((e.metaKey || e.ctrlKey) && ['s', 'u', 'p'].includes(key)) {
          e.preventDefault();
        }
      });
    })();

    /* ACTIVE NAV HIGHLIGHT via IntersectionObserver */
    (function() {
      var sections = document.querySelectorAll('div[id="about"], div[id="events"], div[id="people"], div[id="join"]');
      var navLinks = document.querySelectorAll('.nav-links a');

      function setActive(id) {
        navLinks.forEach(function(a) {
          a.classList.toggle('nav-active', a.getAttribute('href') === '#' + id);
        });
      }

      function clearActive() {
        navLinks.forEach(function(a) { a.classList.remove('nav-active'); });
      }

      var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) setActive(entry.target.id);
        });
      }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });

      sections.forEach(function(s) { observer.observe(s); });

      /* clear highlight when scrolled back to hero */
      window.addEventListener('scroll', function() {
        if (window.scrollY < window.innerHeight * 0.5) clearActive();
      }, { passive: true });
    })();

    /* CONTACT FORM */
    (function() {
      var form     = document.getElementById('contactForm');
      var btn      = document.getElementById('contactSubmit');
      var feedback = document.getElementById('contactFeedback');
      var subject  = document.getElementById('contactSubject');
      var counter  = document.getElementById('subjectCounter');
      var MAX      = 78;

      subject.addEventListener('input', function() {
        var remaining = MAX - subject.value.length;
        counter.textContent = remaining;
        counter.classList.toggle('warn', remaining <= 10);
      });

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.textContent = 'Sending\u2026';
        feedback.textContent = '';
        feedback.className = 'contact-feedback';

        function resetBtn() {
          btn.textContent = 'Send Message';
          btn.disabled = false;
        }

        fetch('contact.php', {
          method: 'POST',
          body: new FormData(form)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data.success) {
            form.reset();
            counter.textContent = MAX;
            counter.classList.remove('warn');
            feedback.textContent = data.message;
            feedback.classList.add('success');
          } else {
            feedback.textContent = data.message;
            feedback.classList.add('error');
          }
          resetBtn();
        })
        .catch(function() {
          feedback.textContent = 'Something went wrong. Please try again.';
          feedback.classList.add('error');
          resetBtn();
        });
      });
    })();
  </script>
</body>
</html>
