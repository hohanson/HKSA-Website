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

$base       = __DIR__ . '/../private/hksa/';
$events     = load_json($base . 'events.json');
$people     = load_json($base . 'people.json');
$links      = load_json($base . 'links.json');
$recaptcha    = load_json($base . 'recaptcha.json');
$rc_version   = $recaptcha['version'] ?? 'v3';
$rc_site_key  = $recaptcha[$rc_version]['site_key'] ?? '';

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
  <title>Stanford HKSA - Hong Kong Student Association</title>
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <meta property="og:image" content="assets/og-image.jpg" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&family=Noto+Serif+TC:wght@400;700&family=Noto+Sans+TC:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-TLH5792XHT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-TLH5792XHT');
  </script>

  <!-- reCAPTCHA <?= e($rc_version) ?> -->
  <?php if ($rc_site_key !== '' && $rc_version === 'v3'): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= e($rc_site_key) ?>"></script>
  <?php elseif ($rc_site_key !== '' && $rc_version === 'v2'): ?>
  <script src="https://www.google.com/recaptcha/api.js"></script>
  <?php endif; ?>
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
            <p lang="zh-Hant" style="font-family:var(--font-display);font-size:clamp(1.8rem,3vw,2.6rem);font-weight:700;color:var(--black);letter-spacing:0.08em;margin-bottom:0.6rem;">史丹福大學 香港學生會</p>
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
    <p class="section-body">From food events and mahjong nights to karaoke and movie screenings, we bring Hong Kong culture to life on campus.</p>
    <div class="carousel-wrap" id="eventsCarousel">
      <button class="carousel-arr" id="evtPrev" onclick="evtMove(-1)" aria-label="Previous" hidden>&#8249;</button>
      <div class="carousel-viewport">
        <div class="carousel-grid" id="evtGrid">
          <?php foreach ($events as $ev): ?>
          <div class="card">
            <div class="card-tag"><?= e($ev['tag'] ?? '') ?></div>
            <h3><?= e($ev['name'] ?? '') ?></h3>
            <p><?= e($ev['description'] ?? '') ?></p>
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
        <img src="<?= e($o['headshot'] ?? '') ?>" alt="<?= e($o['name'] ?? '') ?>" class="officer-photo" width="250" height="250" />
        <div class="card-tag"><?= e($o['role'] ?? '') ?></div>
        <h3><?= e($o['name'] ?? '') ?></h3>
        <?php if (!empty($o['bio'])): ?><p><?= e($o['bio']) ?></p><?php endif; ?>
        <?php if (!empty($o['email'][0])): ?>
        <p><a href="mailto:<?= e($o['email'][0]) ?>" style="color:var(--red);font-size:0.85rem;"><?= e($o['email'][0]) ?></a></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <button class="past-toggle" id="pastToggle" onclick="
      this.classList.toggle('open');
      document.getElementById('pastOfficers').classList.toggle('open');
      this.querySelector('.toggle-label').textContent = this.classList.contains('open') ? 'Hide past officers' : 'Show past officers';
      this.setAttribute('aria-expanded', this.classList.contains('open') ? 'true' : 'false');
      document.documentElement.classList.toggle('no-snap', this.classList.contains('open'));
    " aria-expanded="false">
      <span class="toggle-label">Show past officers</span>
      <i class="toggle-chevron">&#9660;</i>
    </button>

    <div class="past-officers" id="pastOfficers">
      <?php foreach ($past_years as $yr): ?>
      <div class="past-year-label"><?= e(str_replace('-', "\u{2013}", $yr['year'] ?? '')) ?></div>
      <div class="card-grid">
        <?php foreach (($yr['officers'] ?? []) as $o): ?>
        <div class="card">
          <div class="card-tag"><?= e($o['role'] ?? '') ?></div>
          <h3><?= e($o['name'] ?? '') ?></h3>
          <?php /* emails stored in JSON but deliberately not rendered */ ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($yr['exec_team'])): ?>
      <div class="exec-row">
        <span class="card-tag" style="margin-bottom:0;">Executive Team</span>
        <span class="exec-names"><?= e(implode(' · ', array_map(fn($m) => $m['name'] ?? '', $yr['exec_team']))) ?></span>
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
        <!-- reCAPTCHA v2 widget -->
        <?php if ($rc_version === 'v2' && $rc_site_key !== ''): ?>
        <div class="g-recaptcha" data-sitekey="<?= e($rc_site_key) ?>"></div>
        <?php endif; ?>
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
      var sections = document.querySelectorAll('#about, #events, #people, #join, #contact');
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

      window.addEventListener('scroll', function() {
        /* clear highlight when scrolled back to hero */
        if (window.scrollY < window.innerHeight * 0.5) { clearActive(); return; }
        /* short footer at page end may never reach the observer's center band — force Contact */
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
          setActive('contact');
        }
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
      var SITE_KEY = '<?= e($rc_site_key) ?>';
      var RC_VER   = '<?= e($rc_version) ?>';

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
          if (RC_VER === 'v2' && typeof grecaptcha !== 'undefined') {
            grecaptcha.reset();
          }
        }

        function doSubmit(token) {
          var fd = new FormData(form);
          fd.append('g-recaptcha-response', token || '');
          fetch('contact.php', { method: 'POST', body: fd })
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
        }

        if (SITE_KEY && typeof grecaptcha !== 'undefined') {
          if (RC_VER === 'v3') {
            grecaptcha.ready(function() {
              grecaptcha.execute(SITE_KEY, { action: 'contact' })
              .then(function(token) { doSubmit(token); })
              .catch(function() { doSubmit(''); });
            });
          } else {
            /* v2: token already in form via widget, read it directly */
            var token = grecaptcha.getResponse();
            if (token === '') {
              feedback.textContent = 'Please complete the CAPTCHA.';
              feedback.classList.add('error');
              resetBtn();
            } else {
              doSubmit(token);
            }
          }
        } else {
          doSubmit('');
        }
      });
    })();
  </script>
</body>
</html>
