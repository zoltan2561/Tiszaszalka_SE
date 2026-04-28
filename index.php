<?php
$dataFile = __DIR__ . '/data/site.json';
$site = [
  'news' => [],
  'matches' => [],
  'standings' => [],
  'gallery' => [],
  'contact' => [
    'email' => 'info@tiszaszalkase.hu',
    'phone' => '+36 -- --- ----',
  ],
];

if (is_file($dataFile)) {
  $decoded = json_decode((string) file_get_contents($dataFile), true);
  if (is_array($decoded)) {
    $site = array_replace_recursive($site, $decoded);
  }
}

function e(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function excerpt(string $value, int $length = 150): string
{
  $text = trim(preg_replace('/\s+/', ' ', $value));
  if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
    return rtrim(mb_substr($text, 0, $length - 1, 'UTF-8')) . '…';
  }
  if (!function_exists('mb_strlen') && strlen($text) > $length) {
    return rtrim(substr($text, 0, $length - 1)) . '...';
  }
  return $text;
}

$nextMatch = $site['matches'][0] ?? null;
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = strtok($_SERVER['REQUEST_URI'] ?? '/index.php', '?') ?: '/index.php';
$pageUrl = $scheme . '://' . $host . $path;
$basePath = rtrim(str_replace('\\', '/', dirname($path)), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$ogImage = $scheme . '://' . $host . $basePath . '/assets/img/tiszaszalka-se-logo.jpg';
$selectedNewsNumber = isset($_GET['hir']) ? max(1, (int) $_GET['hir']) : 0;
$selectedNewsIndex = $selectedNewsNumber > 0 ? $selectedNewsNumber - 1 : null;
$currentUrl = $selectedNewsNumber > 0 ? $pageUrl . '?hir=' . $selectedNewsNumber : $pageUrl;
$metaTitle = 'Tiszaszalka SE';
$metaDescription = 'Tiszaszalka SE falusi focicsapat hivatalos weboldala.';

if ($selectedNewsIndex !== null && isset($site['news'][$selectedNewsIndex])) {
  $selectedNews = $site['news'][$selectedNewsIndex];
  $metaTitle = (string) ($selectedNews['title'] ?? 'Hír');
  $metaDescription = excerpt((string) ($selectedNews['body'] ?? $metaDescription));
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($metaTitle); ?></title>
  <meta name="description" content="<?php echo e($metaDescription); ?>">
  <link rel="canonical" href="<?php echo e($currentUrl); ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="hu_HU">
  <meta property="og:site_name" content="Tiszaszalka SE">
  <meta property="og:title" content="<?php echo e($metaTitle); ?>">
  <meta property="og:description" content="<?php echo e($metaDescription); ?>">
  <meta property="og:url" content="<?php echo e($currentUrl); ?>">
  <meta property="og:image" content="<?php echo e($ogImage); ?>">
  <meta property="og:image:secure_url" content="<?php echo e($ogImage); ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1280">
  <meta property="og:image:height" content="720">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo e($metaTitle); ?>">
  <meta name="twitter:description" content="<?php echo e($metaDescription); ?>">
  <meta name="twitter:image" content="<?php echo e($ogImage); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="#top" aria-label="Tiszaszalka SE főoldal">
      <img src="assets/img/tiszaszalka-se-crest.jpg" alt="Tiszaszalka SE címer">
      <span>Tiszaszalka SE</span>
    </a>

    <nav class="main-nav" aria-label="Fő navigáció">
      <a href="#hirek">Hírek</a>
      <a href="#meccsek">Meccsek</a>
      <a href="#tabella">Tabella</a>
      <a href="#galeria">Galéria</a>
      <a href="#kapcsolat">Kapcsolat</a>
    </nav>
  </header>

  <main id="top">
    <section class="hero">
      <div class="hero-content">
        <p class="eyebrow">Kell egy csapat</p>
        <h1>Tiszaszalka SE</h1>
        <p class="lead">Hírek, mérkőzések, tabella és képek egy helyen.</p>
        <div class="hero-actions">
          <a class="button primary" href="#meccsek">Következő meccs</a>
          <a class="button secondary" href="#galeria">Galéria</a>
        </div>
      </div>
      <figure class="hero-logo">
        <img src="assets/img/tiszaszalka-se-logo.jpg" alt="Tiszaszalka SE logó">
      </figure>
    </section>

    <section class="quick-info" aria-label="Gyors információk">
      <div><span class="label">Szezon</span><strong>2026/2027</strong></div>
      <div><span class="label">Hazai pálya</span><strong>Tiszaszalka</strong></div>
      <div><span class="label">Jelmondat</span><strong>Kell egy csapat</strong></div>
    </section>

    <section id="hirek" class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Klubélet</p>
          <h2>Hírek</h2>
        </div>
      </div>
      <div class="news-grid">
        <?php if (!empty($site['news'])): ?>
          <?php foreach ($site['news'] as $index => $news): ?>
            <?php
              $newsNumber = $index + 1;
              $newsId = 'hir-' . $newsNumber;
              $newsUrl = $pageUrl . '?hir=' . $newsNumber;
              $shareUrl = 'https://www.facebook.com/sharer.php?u=' . rawurlencode($newsUrl);
            ?>
            <article id="<?php echo e($newsId); ?>" class="card<?php echo $selectedNewsNumber === $newsNumber ? ' selected-news' : ''; ?>">
              <span class="date"><?php echo e($news['date'] ?? ''); ?></span>
              <h3><?php echo e($news['title'] ?? ''); ?></h3>
              <p><?php echo e($news['body'] ?? ''); ?></p>
              <a class="share-button" href="<?php echo e($shareUrl); ?>" aria-label="Megosztás Facebookon" title="Megosztás Facebookon">f</a>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <?php
            $fallbackNews = [
              ['id' => 'hir-1', 'title' => 'Első hír címe', 'body' => 'Ide kerülhetnek a csapat hírei, közleményei és mérkőzésbeszámolói.'],
              ['id' => 'hir-2', 'title' => 'Edzés és program', 'body' => 'Rövid bejegyzésekhez, időpontokhoz és eseményekhez készült hely.'],
              ['id' => 'hir-3', 'title' => 'Közösségi hírek', 'body' => 'Szurkolói információk, támogatók és helyi események jelenhetnek meg itt.'],
            ];
          ?>
          <?php foreach ($fallbackNews as $item): ?>
            <?php
              $newsNumber = (int) str_replace('hir-', '', $item['id']);
              $newsUrl = $pageUrl . '?hir=' . $newsNumber;
              $shareUrl = 'https://www.facebook.com/sharer.php?u=' . rawurlencode($newsUrl);
            ?>
            <article id="<?php echo e($item['id']); ?>" class="card<?php echo $selectedNewsNumber === $newsNumber ? ' selected-news' : ''; ?>">
              <span class="date">Hamarosan</span>
              <h3><?php echo e($item['title']); ?></h3>
              <p><?php echo e($item['body']); ?></p>
              <a class="share-button" href="<?php echo e($shareUrl); ?>" aria-label="Megosztás Facebookon" title="Megosztás Facebookon">f</a>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section id="meccsek" class="section split">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Program</p>
          <h2>Mérkőzések</h2>
        </div>
      </div>
      <div class="match-panel">
        <div class="next-match">
          <span class="label">Következő mérkőzés</span>
          <h3><?php echo e($nextMatch['teams'] ?? 'Tiszaszalka SE - Ellenfél'); ?></h3>
          <p><?php echo e($nextMatch['date'] ?? 'Dátum, időpont és helyszín később kerül feltöltésre.'); ?></p>
        </div>
        <div class="table-scroll">
          <table>
            <thead><tr><th>Dátum</th><th>Mérkőzés</th><th>Eredmény</th></tr></thead>
            <tbody>
              <?php if (!empty($site['matches'])): ?>
                <?php foreach ($site['matches'] as $match): ?>
                  <tr>
                    <td><?php echo e($match['date'] ?? ''); ?></td>
                    <td><?php echo e($match['teams'] ?? ''); ?></td>
                    <td><?php echo e($match['result'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td>--</td><td>Tiszaszalka SE - Ellenfél</td><td>-</td></tr>
                <tr><td>--</td><td>Ellenfél - Tiszaszalka SE</td><td>-</td></tr>
                <tr><td>--</td><td>Tiszaszalka SE - Ellenfél</td><td>-</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="tabella" class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Bajnokság</p>
          <h2>Tabella</h2>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Csapat</th><th>M</th><th>Gy</th><th>D</th><th>V</th><th>P</th></tr></thead>
          <tbody>
            <?php if (!empty($site['standings'])): ?>
              <?php foreach ($site['standings'] as $row): ?>
                <tr>
                  <td><?php echo e($row['position'] ?? ''); ?></td>
                  <td><?php echo e($row['team'] ?? ''); ?></td>
                  <td><?php echo e($row['played'] ?? '0'); ?></td>
                  <td><?php echo e($row['won'] ?? '0'); ?></td>
                  <td><?php echo e($row['drawn'] ?? '0'); ?></td>
                  <td><?php echo e($row['lost'] ?? '0'); ?></td>
                  <td><?php echo e($row['points'] ?? '0'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td>1.</td><td>Tiszaszalka SE</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
              <tr><td>2.</td><td>Ellenfél</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
              <tr><td>3.</td><td>Ellenfél</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="galeria" class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Képek</p>
          <h2>Galéria</h2>
        </div>
      </div>
      <?php if (!empty($site['gallery'])): ?>
        <div class="gallery-carousel" aria-label="Galéria körhinta">
          <button class="carousel-btn prev" type="button" aria-label="Előző kép">‹</button>
          <div class="carousel-track">
            <?php foreach ($site['gallery'] as $photo): ?>
              <?php $caption = (string) ($photo['caption'] ?? 'Tiszaszalka SE kép'); ?>
              <button class="carousel-slide" type="button" data-full="<?php echo e($photo['path'] ?? ''); ?>" data-caption="<?php echo e($caption); ?>">
                <img src="<?php echo e($photo['path'] ?? ''); ?>" alt="<?php echo e($caption); ?>">
                <?php if ($caption !== ''): ?><span><?php echo e($caption); ?></span><?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
          <button class="carousel-btn next" type="button" aria-label="Következő kép">›</button>
        </div>
      <?php else: ?>
        <div class="gallery-carousel">
          <div class="carousel-track">
            <div class="photo-placeholder">Kép helye</div>
            <div class="photo-placeholder">Kép helye</div>
            <div class="photo-placeholder">Kép helye</div>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <section id="kapcsolat" class="section contact">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Elérhetőség</p>
          <h2>Kapcsolat</h2>
        </div>
      </div>
      <div class="contact-grid">
        <div><span class="label">Klub</span><strong>Tiszaszalka SE</strong></div>
        <div><span class="label">Email</span><strong><?php echo e($site['contact']['email'] ?? ''); ?></strong></div>
        <div><span class="label">Telefon</span><strong><?php echo e($site['contact']['phone'] ?? ''); ?></strong></div>
      </div>
    </section>
  </main>

  <footer class="footer_section">
    <div class="text-center">
      <a href="https://pzoli.com" target="_blank" rel="noopener" class="matrix-link">
        &copy; <?php echo date('Y'); ?> BY: pZoli.com
      </a>
    </div>
  </footer>

  <div class="gallery-modal" hidden>
    <button class="modal-close" type="button" aria-label="Bezárás">×</button>
    <button class="modal-nav modal-prev" type="button" aria-label="Előző kép">‹</button>
    <figure>
      <img src="" alt="">
      <figcaption></figcaption>
    </figure>
    <button class="modal-nav modal-next" type="button" aria-label="Következő kép">›</button>
  </div>

  <script>
    const track = document.querySelector('.carousel-track');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    const nextBtn = document.querySelector('.carousel-btn.next');
    const slides = Array.from(document.querySelectorAll('.carousel-slide'));
    const modal = document.querySelector('.gallery-modal');
    const modalImg = modal?.querySelector('img');
    const modalCaption = modal?.querySelector('figcaption');
    let activeIndex = 0;

    function scrollGallery(direction) {
      if (!track) return;
      track.scrollBy({ left: direction * track.clientWidth * 0.85, behavior: 'smooth' });
    }

    function openModal(index) {
      if (!modal || !modalImg || !slides[index]) return;
      activeIndex = index;
      const slide = slides[activeIndex];
      modalImg.src = slide.dataset.full;
      modalImg.alt = slide.dataset.caption || 'Tiszaszalka SE kép';
      modalCaption.textContent = slide.dataset.caption || '';
      modal.hidden = false;
      document.body.classList.add('modal-open');
    }

    function closeModal() {
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    }

    function moveModal(direction) {
      if (slides.length === 0) return;
      activeIndex = (activeIndex + direction + slides.length) % slides.length;
      openModal(activeIndex);
    }

    prevBtn?.addEventListener('click', () => scrollGallery(-1));
    nextBtn?.addEventListener('click', () => scrollGallery(1));
    slides.forEach((slide, index) => slide.addEventListener('click', () => openModal(index)));
    modal?.querySelector('.modal-close')?.addEventListener('click', closeModal);
    modal?.querySelector('.modal-prev')?.addEventListener('click', () => moveModal(-1));
    modal?.querySelector('.modal-next')?.addEventListener('click', () => moveModal(1));
    modal?.addEventListener('click', (event) => {
      if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
      if (!modal || modal.hidden) return;
      if (event.key === 'Escape') closeModal();
      if (event.key === 'ArrowLeft') moveModal(-1);
      if (event.key === 'ArrowRight') moveModal(1);
    });

    const selectedNews = document.querySelector('.selected-news');
    selectedNews?.scrollIntoView({ block: 'center' });
  </script>
</body>
</html>
