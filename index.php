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
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tiszaszalka SE</title>
  <meta name="description" content="Tiszaszalka SE falusi focicsapat hivatalos weboldala.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="#top" aria-label="Tiszaszalka SE főoldal">
      <img src="assets/img/tiszaszalka-se-logo.jpg" alt="Tiszaszalka SE címeres logó">
      <span>Tiszaszalka SE</span>
    </a>

    <nav class="main-nav" aria-label="Fő navigáció">
      <a href="#hirek">Hírek</a>
      <a href="#meccsek">Meccsek</a>
      <a href="#tabella">Tabella</a>
      <a href="#galeria">Galéria</a>
      <a href="#kapcsolat">Kapcsolat</a>
      <a href="admin.php">Admin</a>
    </nav>
  </header>

  <main id="top">
    <section class="hero">
      <div class="hero-content">
        <p class="eyebrow">Falusi focicsapat</p>
        <h1>Tiszaszalka SE</h1>
        <p class="lead">Kell egy csapat. Hírek, mérkőzések, tabellák és képek egy helyen.</p>
        <div class="hero-actions">
          <a class="button primary" href="#meccsek">Következő meccs</a>
          <a class="button secondary" href="#galeria">Képek helye</a>
        </div>
      </div>
      <figure class="hero-logo">
        <img src="assets/img/tiszaszalka-se-logo.jpg" alt="Tiszaszalka SE logó zöld pályás háttérrel">
      </figure>
    </section>

    <section class="quick-info" aria-label="Gyors információk">
      <div><span class="label">Szezon</span><strong>2026/2027</strong></div>
      <div><span class="label">Hazai pálya</span><strong>Tiszaszalka</strong></div>
      <div><span class="label">Jelmondat</span><strong>Kell egy csapat</strong></div>
    </section>

    <section id="hirek" class="section">
      <div class="section-heading">
        <p class="eyebrow">Klubélet</p>
        <h2>Hírek</h2>
      </div>
      <div class="news-grid">
        <?php if (!empty($site['news'])): ?>
          <?php foreach ($site['news'] as $news): ?>
            <article class="card">
              <span class="date"><?php echo e($news['date'] ?? ''); ?></span>
              <h3><?php echo e($news['title'] ?? ''); ?></h3>
              <p><?php echo e($news['body'] ?? ''); ?></p>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <article class="card"><span class="date">Hamarosan</span><h3>Első hír címe</h3><p>Ide kerülhetnek a csapat hírei, közleményei, igazolások vagy mérkőzésbeszámolók.</p></article>
          <article class="card"><span class="date">Hamarosan</span><h3>Edzés és program</h3><p>Rövid bejegyzésekhez, időpontokhoz és eseményekhez készült hely.</p></article>
          <article class="card"><span class="date">Hamarosan</span><h3>Közösségi hírek</h3><p>Szurkolói információk, támogatók és helyi események jelenhetnek meg itt.</p></article>
        <?php endif; ?>
      </div>
    </section>

    <section id="meccsek" class="section split">
      <div class="section-heading">
        <p class="eyebrow">Program</p>
        <h2>Mérkőzések</h2>
      </div>
      <div class="match-panel">
        <div class="next-match">
          <span class="label">Következő mérkőzés</span>
          <?php $nextMatch = $site['matches'][0] ?? null; ?>
          <h3><?php echo e($nextMatch['teams'] ?? 'Tiszaszalka SE - Ellenfél'); ?></h3>
          <p><?php echo e($nextMatch['date'] ?? 'Dátum, időpont és helyszín később kerül feltöltésre.'); ?></p>
        </div>
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
    </section>

    <section id="tabella" class="section">
      <div class="section-heading">
        <p class="eyebrow">Bajnokság</p>
        <h2>Tabella</h2>
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
        <p class="eyebrow">Képek</p>
        <h2>Galéria</h2>
      </div>
      <div class="gallery-grid">
        <?php if (!empty($site['gallery'])): ?>
          <?php foreach ($site['gallery'] as $photo): ?>
            <figure class="gallery-photo">
              <img src="<?php echo e($photo['path'] ?? ''); ?>" alt="<?php echo e($photo['caption'] ?? 'Tiszaszalka SE kép'); ?>">
              <?php if (!empty($photo['caption'])): ?><figcaption><?php echo e($photo['caption']); ?></figcaption><?php endif; ?>
            </figure>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="photo-placeholder">Kép helye</div>
          <div class="photo-placeholder">Kép helye</div>
          <div class="photo-placeholder">Kép helye</div>
          <div class="photo-placeholder">Kép helye</div>
        <?php endif; ?>
      </div>
    </section>

    <section id="kapcsolat" class="section contact">
      <div class="section-heading">
        <p class="eyebrow">Elérhetőség</p>
        <h2>Kapcsolat</h2>
      </div>
      <div class="contact-grid">
        <div><span class="label">Klub</span><strong>Tiszaszalka SE</strong></div>
        <div><span class="label">Email</span><strong><?php echo e($site['contact']['email'] ?? ''); ?></strong></div>
        <div><span class="label">Telefon</span><strong><?php echo e($site['contact']['phone'] ?? ''); ?></strong></div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-newsletter">
      <div>
        <p class="eyebrow">Hírlevél</p>
        <h2>Iratkozz fel híreinkre</h2>
        <p>Értesülj elsőként a mérkőzésekről, eredményekről és klubhírekről.</p>
      </div>
      <form class="newsletter-form" action="#" method="post">
        <label class="sr-only" for="newsletter-email">E-mail cím</label>
        <input id="newsletter-email" type="email" placeholder="E-mail" aria-label="E-mail">
        <button class="button primary" type="submit">Feliratkozás</button>
      </form>
    </div>

    <div class="footer-main">
      <div class="footer-brand">
        <h3>Tiszaszalka SE</h3>
        <p>Falusi focicsapat. Kell egy csapat.</p>
        <p>Hazai pálya: Tiszaszalka</p>
      </div>
      <div>
        <h3>Oldalak</h3>
        <a href="#hirek">Hírek</a>
        <a href="#meccsek">Mérkőzések</a>
        <a href="#tabella">Tabella</a>
        <a href="#galeria">Galéria</a>
      </div>
      <div>
        <h3>Egyéb</h3>
        <a href="admin.php">Admin</a>
        <a href="#kapcsolat">Kapcsolat</a>
      </div>
      <div>
        <h3>Kapcsolat</h3>
        <p><?php echo e($site['contact']['phone'] ?? ''); ?></p>
        <p><?php echo e($site['contact']['email'] ?? ''); ?></p>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?php echo date('Y'); ?> Tiszaszalka SE</span>
      <span>Készítette: <a href="https://pzoli.com" target="_blank" rel="noopener">pzoli.com</a></span>
    </div>
  </footer>
</body>
</html>
