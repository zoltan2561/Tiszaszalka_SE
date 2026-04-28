<?php
session_start();

$adminPassword = 'tiszaadmin';
$dataDir = __DIR__ . '/data';
$uploadDir = __DIR__ . '/assets/img/gallery';
$dataFile = $dataDir . '/site.json';

if (!is_dir($dataDir)) {
  mkdir($dataDir, 0775, true);
}

if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0775, true);
}

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

function clean_text(?string $value): string
{
  return trim((string) $value);
}

function clean_index(?string $value): int
{
  return max(0, (int) $value);
}

function save_site(string $dataFile, array $site): void
{
  file_put_contents(
    $dataFile,
    json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
  );
}

function remove_gallery_file(array $photo): void
{
  $path = (string) ($photo['path'] ?? '');
  if ($path === '') {
    return;
  }

  $fullPath = realpath(__DIR__ . '/' . $path);
  $galleryRoot = realpath(__DIR__ . '/assets/img/gallery');
  if ($fullPath && $galleryRoot && str_starts_with($fullPath, $galleryRoot) && is_file($fullPath)) {
    unlink($fullPath);
  }
}

$message = '';
$loginError = '';

if (isset($_GET['logout'])) {
  $_SESSION = [];
  session_destroy();
  header('Location: admin.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  if (hash_equals($adminPassword, (string) ($_POST['password'] ?? ''))) {
    $_SESSION['admin_logged_in'] = true;
    header('Location: admin.php');
    exit;
  }

  $loginError = 'Hibás jelszó.';
}

$isLoggedIn = !empty($_SESSION['admin_logged_in']);

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $index = clean_index($_POST['index'] ?? null);

  if ($action === 'contact') {
    $site['contact']['email'] = clean_text($_POST['email'] ?? '');
    $site['contact']['phone'] = clean_text($_POST['phone'] ?? '');
    $message = 'Kapcsolati adatok mentve.';
  }

  if ($action === 'news') {
    $site['news'][] = [
      'date' => clean_text($_POST['date'] ?? ''),
      'title' => clean_text($_POST['title'] ?? ''),
      'body' => clean_text($_POST['body'] ?? ''),
    ];
    $message = 'Hír hozzáadva.';
  }

  if ($action === 'update_news' && isset($site['news'][$index])) {
    $site['news'][$index] = [
      'date' => clean_text($_POST['date'] ?? ''),
      'title' => clean_text($_POST['title'] ?? ''),
      'body' => clean_text($_POST['body'] ?? ''),
    ];
    $message = 'Hír frissítve.';
  }

  if ($action === 'delete_news' && isset($site['news'][$index])) {
    array_splice($site['news'], $index, 1);
    $message = 'Hír törölve.';
  }

  if ($action === 'match') {
    $site['matches'][] = [
      'date' => clean_text($_POST['date'] ?? ''),
      'teams' => clean_text($_POST['teams'] ?? ''),
      'result' => clean_text($_POST['result'] ?? '-'),
    ];
    $message = 'Mérkőzés hozzáadva.';
  }

  if ($action === 'update_match' && isset($site['matches'][$index])) {
    $site['matches'][$index] = [
      'date' => clean_text($_POST['date'] ?? ''),
      'teams' => clean_text($_POST['teams'] ?? ''),
      'result' => clean_text($_POST['result'] ?? '-'),
    ];
    $message = 'Mérkőzés frissítve.';
  }

  if ($action === 'delete_match' && isset($site['matches'][$index])) {
    array_splice($site['matches'], $index, 1);
    $message = 'Mérkőzés törölve.';
  }

  if ($action === 'standing') {
    $site['standings'][] = [
      'position' => clean_text($_POST['position'] ?? ''),
      'team' => clean_text($_POST['team'] ?? ''),
      'played' => clean_text($_POST['played'] ?? '0'),
      'won' => clean_text($_POST['won'] ?? '0'),
      'drawn' => clean_text($_POST['drawn'] ?? '0'),
      'lost' => clean_text($_POST['lost'] ?? '0'),
      'points' => clean_text($_POST['points'] ?? '0'),
    ];
    $message = 'Tabella sor hozzáadva.';
  }

  if ($action === 'update_standing' && isset($site['standings'][$index])) {
    $site['standings'][$index] = [
      'position' => clean_text($_POST['position'] ?? ''),
      'team' => clean_text($_POST['team'] ?? ''),
      'played' => clean_text($_POST['played'] ?? '0'),
      'won' => clean_text($_POST['won'] ?? '0'),
      'drawn' => clean_text($_POST['drawn'] ?? '0'),
      'lost' => clean_text($_POST['lost'] ?? '0'),
      'points' => clean_text($_POST['points'] ?? '0'),
    ];
    $message = 'Tabella sor frissítve.';
  }

  if ($action === 'delete_standing' && isset($site['standings'][$index])) {
    array_splice($site['standings'], $index, 1);
    $message = 'Tabella sor törölve.';
  }

  if ($action === 'gallery' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);

    if (isset($allowed[$mime])) {
      $fileName = 'kep-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
      $target = $uploadDir . '/' . $fileName;

      if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $site['gallery'][] = [
          'path' => 'assets/img/gallery/' . $fileName,
          'caption' => clean_text($_POST['caption'] ?? ''),
        ];
        $message = 'Kép feltöltve.';
      }
    } else {
      $message = 'Csak JPG, PNG vagy WEBP kép tölthető fel.';
    }
  }

  if ($action === 'update_gallery' && isset($site['gallery'][$index])) {
    $site['gallery'][$index]['caption'] = clean_text($_POST['caption'] ?? '');
    $message = 'Képaláírás frissítve.';
  }

  if ($action === 'delete_gallery' && isset($site['gallery'][$index])) {
    remove_gallery_file($site['gallery'][$index]);
    array_splice($site['gallery'], $index, 1);
    $message = 'Kép törölve.';
  }

  if ($action === 'clear') {
    $section = $_POST['section'] ?? '';
    if (isset($site[$section]) && is_array($site[$section]) && $section !== 'contact') {
      if ($section === 'gallery') {
        foreach ($site['gallery'] as $photo) {
          remove_gallery_file($photo);
        }
      }
      $site[$section] = [];
      $message = 'Szekció ürítve.';
    }
  }

  if ($action !== 'login') {
    save_site($dataFile, $site);
  }
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Tiszaszalka SE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">
      <img src="assets/img/tiszaszalka-se-crest.jpg" alt="Tiszaszalka SE címer">
      <span>Tiszaszalka SE Admin</span>
    </a>
    <nav class="main-nav">
      <a href="index.php">Weboldal</a>
      <?php if ($isLoggedIn): ?><a href="admin.php?logout=1">Kilépés</a><?php endif; ?>
    </nav>
  </header>

  <main class="admin-page">
    <?php if (!$isLoggedIn): ?>
      <section class="admin-login">
        <form class="admin-card login-card" method="post">
          <input type="hidden" name="action" value="login">
          <p class="eyebrow">Belépés</p>
          <h1>Admin felület</h1>
          <label>Jelszó<input name="password" type="password" required autofocus></label>
          <?php if ($loginError !== ''): ?><div class="notice error"><?php echo e($loginError); ?></div><?php endif; ?>
          <button class="button primary" type="submit">Belépés</button>
        </form>
      </section>
    <?php else: ?>
      <section class="admin-hero">
        <p class="eyebrow">Adatkezelés</p>
        <h1>Admin felület</h1>
        <p>Itt láthatók, szerkeszthetők és törölhetők a weboldalon megjelenő adatok.</p>
        <?php if ($message !== ''): ?><div class="notice"><?php echo e($message); ?></div><?php endif; ?>
      </section>

      <section class="admin-grid">
        <form class="admin-card" method="post">
          <input type="hidden" name="action" value="news">
          <h2>Új hír</h2>
          <label>Dátum<input name="date" type="text" placeholder="2026.04.28."></label>
          <label>Cím<input name="title" type="text" required></label>
          <label>Szöveg<textarea name="body" rows="5" required></textarea></label>
          <button class="button primary" type="submit">Hozzáadás</button>
        </form>

        <form class="admin-card" method="post">
          <input type="hidden" name="action" value="match">
          <h2>Új mérkőzés</h2>
          <label>Dátum<input name="date" type="text" placeholder="2026.05.03. 16:00"></label>
          <label>Mérkőzés<input name="teams" type="text" placeholder="Tiszaszalka SE - Ellenfél" required></label>
          <label>Eredmény<input name="result" type="text" placeholder="-"></label>
          <button class="button primary" type="submit">Hozzáadás</button>
        </form>

        <form class="admin-card" method="post">
          <input type="hidden" name="action" value="standing">
          <h2>Új tabella sor</h2>
          <div class="form-row">
            <label>Hely<input name="position" type="text" placeholder="1."></label>
            <label>Csapat<input name="team" type="text" required></label>
          </div>
          <div class="form-row six">
            <label>M<input name="played" type="number" min="0" value="0"></label>
            <label>Gy<input name="won" type="number" min="0" value="0"></label>
            <label>D<input name="drawn" type="number" min="0" value="0"></label>
            <label>V<input name="lost" type="number" min="0" value="0"></label>
            <label>P<input name="points" type="number" min="0" value="0"></label>
          </div>
          <button class="button primary" type="submit">Hozzáadás</button>
        </form>

        <form class="admin-card" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="gallery">
          <h2>Új galéria kép</h2>
          <label>Képaláírás<input name="caption" type="text"></label>
          <label>Kép<input name="image" type="file" accept="image/jpeg,image/png,image/webp" required></label>
          <button class="button primary" type="submit">Feltöltés</button>
        </form>

        <form class="admin-card" method="post">
          <input type="hidden" name="action" value="contact">
          <h2>Kapcsolat</h2>
          <label>Email<input name="email" type="email" value="<?php echo e($site['contact']['email'] ?? ''); ?>"></label>
          <label>Telefon<input name="phone" type="text" value="<?php echo e($site['contact']['phone'] ?? ''); ?>"></label>
          <button class="button primary" type="submit">Mentés</button>
        </form>

        <div class="admin-card">
          <h2>Adatok ürítése</h2>
          <form method="post" class="clear-actions">
            <input type="hidden" name="action" value="clear">
            <button name="section" value="news" class="button secondary dark" type="submit">Hírek ürítése</button>
            <button name="section" value="matches" class="button secondary dark" type="submit">Meccsek ürítése</button>
            <button name="section" value="standings" class="button secondary dark" type="submit">Tabella ürítése</button>
            <button name="section" value="gallery" class="button secondary dark" type="submit">Galéria ürítése</button>
          </form>
        </div>
      </section>

      <section class="admin-list-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Jelenlegi tartalom</p>
            <h2>Hírek</h2>
          </div>
        </div>
        <div class="admin-list">
          <?php if (empty($site['news'])): ?>
            <p class="empty-state">Nincs még hír feltöltve.</p>
          <?php endif; ?>
          <?php foreach ($site['news'] as $index => $news): ?>
            <form class="admin-edit-card" method="post">
              <input type="hidden" name="index" value="<?php echo $index; ?>">
              <label>Dátum<input name="date" type="text" value="<?php echo e($news['date'] ?? ''); ?>"></label>
              <label>Cím<input name="title" type="text" value="<?php echo e($news['title'] ?? ''); ?>"></label>
              <label>Szöveg<textarea name="body" rows="4"><?php echo e($news['body'] ?? ''); ?></textarea></label>
              <div class="admin-actions">
                <button class="button primary" name="action" value="update_news" type="submit">Mentés</button>
                <button class="button danger" name="action" value="delete_news" type="submit">Törlés</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="admin-list-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Jelenlegi tartalom</p>
            <h2>Mérkőzések</h2>
          </div>
        </div>
        <div class="admin-list">
          <?php if (empty($site['matches'])): ?>
            <p class="empty-state">Nincs még mérkőzés feltöltve.</p>
          <?php endif; ?>
          <?php foreach ($site['matches'] as $index => $match): ?>
            <form class="admin-edit-card compact" method="post">
              <input type="hidden" name="index" value="<?php echo $index; ?>">
              <label>Dátum<input name="date" type="text" value="<?php echo e($match['date'] ?? ''); ?>"></label>
              <label>Mérkőzés<input name="teams" type="text" value="<?php echo e($match['teams'] ?? ''); ?>"></label>
              <label>Eredmény<input name="result" type="text" value="<?php echo e($match['result'] ?? '-'); ?>"></label>
              <div class="admin-actions">
                <button class="button primary" name="action" value="update_match" type="submit">Mentés</button>
                <button class="button danger" name="action" value="delete_match" type="submit">Törlés</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="admin-list-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Jelenlegi tartalom</p>
            <h2>Tabella</h2>
          </div>
        </div>
        <div class="admin-list">
          <?php if (empty($site['standings'])): ?>
            <p class="empty-state">Nincs még tabella sor feltöltve.</p>
          <?php endif; ?>
          <?php foreach ($site['standings'] as $index => $row): ?>
            <form class="admin-edit-card standing-edit" method="post">
              <input type="hidden" name="index" value="<?php echo $index; ?>">
              <label>Hely<input name="position" type="text" value="<?php echo e($row['position'] ?? ''); ?>"></label>
              <label>Csapat<input name="team" type="text" value="<?php echo e($row['team'] ?? ''); ?>"></label>
              <label>M<input name="played" type="number" min="0" value="<?php echo e($row['played'] ?? '0'); ?>"></label>
              <label>Gy<input name="won" type="number" min="0" value="<?php echo e($row['won'] ?? '0'); ?>"></label>
              <label>D<input name="drawn" type="number" min="0" value="<?php echo e($row['drawn'] ?? '0'); ?>"></label>
              <label>V<input name="lost" type="number" min="0" value="<?php echo e($row['lost'] ?? '0'); ?>"></label>
              <label>P<input name="points" type="number" min="0" value="<?php echo e($row['points'] ?? '0'); ?>"></label>
              <div class="admin-actions">
                <button class="button primary" name="action" value="update_standing" type="submit">Mentés</button>
                <button class="button danger" name="action" value="delete_standing" type="submit">Törlés</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="admin-list-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Jelenlegi tartalom</p>
            <h2>Galéria</h2>
          </div>
        </div>
        <div class="admin-gallery-list">
          <?php if (empty($site['gallery'])): ?>
            <p class="empty-state">Nincs még galéria kép feltöltve.</p>
          <?php endif; ?>
          <?php foreach ($site['gallery'] as $index => $photo): ?>
            <form class="admin-edit-card gallery-edit" method="post">
              <input type="hidden" name="index" value="<?php echo $index; ?>">
              <img src="<?php echo e($photo['path'] ?? ''); ?>" alt="<?php echo e($photo['caption'] ?? 'Galéria kép'); ?>">
              <label>Képaláírás<input name="caption" type="text" value="<?php echo e($photo['caption'] ?? ''); ?>"></label>
              <div class="admin-actions">
                <button class="button primary" name="action" value="update_gallery" type="submit">Mentés</button>
                <button class="button danger" name="action" value="delete_gallery" type="submit">Törlés</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
