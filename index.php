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
$mlszSourceUrl = 'https://adatbank.mlsz.hu/league/65/16/31845/21.html';
$mlszCacheFile = __DIR__ . '/data/mlsz-cache.json';
$mlszTeamName = 'TISZASZALKA SE';
$mlszCacheTtl = 1800;
$documentsDir = __DIR__ . '/assets/pdf';
$documentsPath = 'assets/pdf';

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

function news_share_url(string $pageUrl, int $newsNumber): string
{
  return $pageUrl . '?hir=' . $newsNumber;
}

function facebook_share_url(string $newsUrl, string $title, string $description): string
{
  $quote = trim($title . "\n\n" . $description);
  $shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($newsUrl);

  if ($quote !== '') {
    $shareUrl .= '&quote=' . rawurlencode($quote);
  }

  return $shareUrl;
}

function article_published_time(string $date): string
{
  $date = trim($date);
  if ($date === '') {
    return '';
  }

  $published = DateTimeImmutable::createFromFormat('!Y.m.d.', $date, new DateTimeZone('Europe/Budapest'));
  if (!$published) {
    return '';
  }

  return $published->format(DATE_ATOM);
}

function format_file_size(int $bytes): string
{
  if ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 1, ',', ' ') . ' MB';
  }

  return max(1, (int) ceil($bytes / 1024)) . ' KB';
}

function document_title(string $filename): string
{
  $name = pathinfo($filename, PATHINFO_FILENAME);
  $name = str_replace(['_', '-'], ' ', $name);
  $name = trim(preg_replace('/\s+/', ' ', $name));

  return $name !== '' ? $name : $filename;
}

function load_documents(string $documentsDir, string $documentsPath): array
{
  if (!is_dir($documentsDir)) {
    return [];
  }

  $documents = [];
  foreach (glob($documentsDir . '/*.pdf') ?: [] as $file) {
    if (!is_file($file)) {
      continue;
    }

    $filename = basename($file);
    $documents[] = [
      'title' => document_title($filename),
      'filename' => $filename,
      'url' => $documentsPath . '/' . rawurlencode($filename),
      'size' => format_file_size((int) filesize($file)),
    ];
  }

  usort($documents, static fn(array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));

  return $documents;
}

function clean_text(string $value): string
{
  $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  if (strpos($value, 'Ã') !== false) {
    $value = strtr($value, [
      "\xc3\x83\xc2\x81" => 'Á', "\xc3\x83\xc2\x89" => 'É', "\xc3\x83\xc2\x8d" => 'Í',
      "\xc3\x83\xe2\x80\x9c" => 'Ó', "\xc3\x83\xe2\x80\x93" => 'Ö', "\xc3\x83\xc5\xa1" => 'Ú', "\xc3\x83\xc5\x93" => 'Ü',
      "\xc3\x83\xc2\xa1" => 'á', "\xc3\x83\xc2\xa9" => 'é', "\xc3\x83\xc2\xad" => 'í',
      "\xc3\x83\xc2\xb3" => 'ó', "\xc3\x83\xc2\xb6" => 'ö', "\xc3\x83\xc2\xba" => 'ú', "\xc3\x83\xc2\xbc" => 'ü',
      "\xc3\x85\xc2\x90" => 'Ő', "\xc3\x85\xe2\x80\x98" => 'ő', "\xc3\x85\xc2\xb0" => 'Ű', "\xc3\x85\xc2\xb1" => 'ű',
    ]);
  }
  $value = str_replace("\xc2\xa0", ' ', $value);
  return trim(preg_replace('/\s+/u', ' ', $value));
}

function fetch_url(string $url): string
{
  $context = stream_context_create([
    'http' => [
      'timeout' => 8,
      'header' => "User-Agent: TiszaszalkaSE/1.0\r\n",
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);

  $html = @file_get_contents($url, false, $context);
  if (is_string($html) && $html !== '') {
    return $html;
  }

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => 6,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_USERAGENT => 'TiszaszalkaSE/1.0',
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    if (is_string($html) && $html !== '') {
      return $html;
    }
  }

  if (stripos(PHP_OS_FAMILY, 'Windows') !== false && function_exists('shell_exec')) {
    $ps = '$ProgressPreference = "SilentlyContinue"; ' .
      '$wc = New-Object System.Net.WebClient; ' .
      '$wc.Headers.Add("User-Agent", "TiszaszalkaSE/1.0"); ' .
      '$bytes = $wc.DownloadData("' . str_replace('"', '\"', $url) . '"); ' .
      '[Convert]::ToBase64String($bytes)';
    $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "' . str_replace('"', '\"', $ps) . '"';
    $encoded = shell_exec($command);
    if (is_string($encoded) && trim($encoded) !== '') {
      $html = base64_decode(trim($encoded), true);
      if (is_string($html) && $html !== '') {
        return $html;
      }
    }
  }

  return '';
}

function parse_mlsz_date(string $value): ?DateTimeImmutable
{
  if (!preg_match('/(\d{4})\.\s*(\d{2})\.\s*(\d{2})\.\s*(\d{1,2}):(\d{2})/u', $value, $matches)) {
    return null;
  }

  return DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    sprintf('%04d-%02d-%02d %02d:%02d', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5]),
    new DateTimeZone('Europe/Budapest')
  ) ?: null;
}

function parse_mlsz_page(string $html, int $round): array
{
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $encodedHtml = iconv('UTF-8', 'HTML-ENTITIES//IGNORE', $html);
  $dom->loadHTML($encodedHtml !== false ? $encodedHtml : $html);
  $xpath = new DOMXPath($dom);
  $matches = [];
  $standings = [];

  foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " schedule ")]') as $schedule) {
    $home = clean_text($xpath->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " home_team ")])', $schedule));
    $away = clean_text($xpath->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " away_team ")])', $schedule));
    $result = clean_text($xpath->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " result-cont ")])', $schedule));
    if (!preg_match('/^\d+\s*-\s*\d+$/', $result)) {
      $result = '-';
    }
    $date = clean_text($xpath->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " team_sorsolas_date ")])', $schedule));
    $place = clean_text($xpath->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " team_sorsolas_arena ")])', $schedule));

    if ($home === '' || $away === '' || $date === '') {
      continue;
    }

    $matches[] = [
      'round' => (string) $round,
      'date' => $date . ($place !== '' ? ', ' . $place : ''),
      'date_raw' => $date,
      'timestamp' => parse_mlsz_date($date)?->getTimestamp() ?? 0,
      'teams' => $home . ' - ' . $away,
      'result' => $result !== '' ? $result : '-',
    ];
  }

  $standingRows = null;
  foreach ($xpath->query('//table[contains(., "Pontszám") and contains(., "BR.*") and contains(., "TISZASZALKA")]') as $table) {
    $standingRows = $xpath->query('.//tbody/tr', $table);
    break;
  }

  foreach ($standingRows ?? [] as $row) {
    $cells = [];
    foreach ($xpath->query('./td', $row) as $cell) {
      $cells[] = clean_text($cell->textContent);
    }

    if (count($cells) < 11 || !ctype_digit($cells[0])) {
      continue;
    }

    $standings[] = [
      'position' => $cells[0],
      'team' => $cells[2] ?? '',
      'played' => $cells[3] ?? '0',
      'won' => $cells[4] ?? '0',
      'drawn' => $cells[5] ?? '0',
      'lost' => $cells[6] ?? '0',
      'points' => $cells[10] ?? '0',
    ];
  }

  return ['matches' => $matches, 'standings' => $standings];
}

function load_mlsz_data(string $sourceUrl, string $cacheFile, string $teamName, int $cacheTtl): array
{
  if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    $cached = json_decode((string) file_get_contents($cacheFile), true);
    if (is_array($cached)) {
      return $cached;
    }
  }

  $allMatches = [];
  $standings = [];
  $sourceHtml = fetch_url($sourceUrl);

  if ($sourceHtml !== '') {
    $baseRound = 21;
    $sourceData = parse_mlsz_page($sourceHtml, $baseRound);
    $allMatches = array_merge($allMatches, $sourceData['matches']);
    $standings = $sourceData['standings'];

    for ($round = $baseRound + 1; $round <= 26; $round++) {
      if ($round === $baseRound) {
        continue;
      }
      $roundUrl = preg_replace('~/\d+\.html$~', '/' . $round . '.html', $sourceUrl);
      if (!is_string($roundUrl)) {
        continue;
      }
      $html = fetch_url($roundUrl);
      if ($html === '') {
        continue;
      }
      $roundData = parse_mlsz_page($html, $round);
      $allMatches = array_merge($allMatches, $roundData['matches']);
    }
  }

  $teamMatches = array_values(array_filter($allMatches, static function (array $match) use ($teamName): bool {
    return stripos($match['teams'] ?? '', $teamName) !== false;
  }));
  usort($teamMatches, static fn(array $a, array $b): int => ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0));
  $uniqueMatches = [];
  foreach ($teamMatches as $match) {
    $key = ($match['date'] ?? '') . '|' . ($match['teams'] ?? '');
    $uniqueMatches[$key] = $match;
  }
  $teamMatches = array_values($uniqueMatches);

  $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Budapest')))->getTimestamp();
  $nextMatch = null;
  foreach ($teamMatches as $match) {
    if (($match['timestamp'] ?? 0) >= $now) {
      $nextMatch = $match;
      break;
    }
  }

  $data = [
    'matches' => array_map(static function (array $match): array {
      unset($match['timestamp'], $match['date_raw']);
      return $match;
    }, array_slice(array_reverse($teamMatches), 0, 8)),
    'standings' => $standings,
    'next_match' => $nextMatch ? [
      'date' => $nextMatch['date'],
      'teams' => $nextMatch['teams'],
      'result' => $nextMatch['result'],
    ] : null,
    'updated_at' => date('Y-m-d H:i:s'),
  ];

  if (!empty($data['matches']) || !empty($data['standings'])) {
    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  }

  return $data;
}

$mlszData = load_mlsz_data($mlszSourceUrl, $mlszCacheFile, $mlszTeamName, $mlszCacheTtl);
if (!empty($mlszData['matches'])) {
  $site['matches'] = $mlszData['matches'];
}
if (!empty($mlszData['standings'])) {
  $site['standings'] = $mlszData['standings'];
}

$nextMatch = $site['matches'][0] ?? null;
if (!empty($mlszData['next_match'])) {
  $nextMatch = $mlszData['next_match'];
}
$documents = load_documents($documentsDir, $documentsPath);
$canonicalHost = 'tiszaszalkase.com';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = strtok($_SERVER['REQUEST_URI'] ?? '/index.php', '?') ?: '/index.php';
$pageUrl = 'https://' . $canonicalHost . $path;
$basePath = rtrim(str_replace('\\', '/', dirname($path)), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$ogImage = 'https://' . $canonicalHost . $basePath . '/assets/img/tiszaszalka-se-logo.jpg';
$selectedNewsNumber = isset($_GET['hir']) ? max(1, (int) $_GET['hir']) : 0;
$selectedNewsIndex = $selectedNewsNumber > 0 ? $selectedNewsNumber - 1 : null;
$currentUrl = $selectedNewsNumber > 0 ? news_share_url($pageUrl, $selectedNewsNumber) : $pageUrl;
$metaTitle = 'Tiszaszalka SE';
$metaDescription = 'Tiszaszalka SE falusi focicsapat hivatalos weboldala.';
$metaType = 'website';
$articlePublishedTime = '';

if ($selectedNewsIndex !== null && isset($site['news'][$selectedNewsIndex])) {
  $selectedNews = $site['news'][$selectedNewsIndex];
  $metaTitle = (string) ($selectedNews['title'] ?? 'Hír');
  $metaDescription = excerpt((string) ($selectedNews['body'] ?? $metaDescription));
  $metaType = 'article';
  $articlePublishedTime = article_published_time((string) ($selectedNews['date'] ?? ''));
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($metaTitle); ?></title>
  <meta name="description" content="<?php echo e($metaDescription); ?>">
  <meta itemprop="name" content="<?php echo e($metaTitle); ?>">
  <meta itemprop="description" content="<?php echo e($metaDescription); ?>">
  <meta itemprop="image" content="<?php echo e($ogImage); ?>">
  <link rel="canonical" href="<?php echo e($currentUrl); ?>">
  <meta property="og:type" content="<?php echo e($metaType); ?>">
  <meta property="og:locale" content="hu_HU">
  <meta property="og:site_name" content="Tiszaszalka SE">
  <meta property="og:title" content="<?php echo e($metaTitle); ?>">
  <meta property="og:description" content="<?php echo e($metaDescription); ?>">
  <meta property="og:url" content="<?php echo e($currentUrl); ?>">
  <meta property="og:image" content="<?php echo e($ogImage); ?>">
  <meta property="og:image:secure_url" content="<?php echo e($ogImage); ?>">
  <meta property="og:image:alt" content="Tiszaszalka SE logo">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1280">
  <meta property="og:image:height" content="720">
  <?php if ($metaType === 'article'): ?>
  <meta property="article:section" content="Hirek">
  <?php if ($articlePublishedTime !== ''): ?>
  <meta property="article:published_time" content="<?php echo e($articlePublishedTime); ?>">
  <?php endif; ?>
  <?php endif; ?>
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
      <a href="#dokumentumok">Dokumentumok</a>
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
              $newsUrl = news_share_url($pageUrl, $newsNumber);
              $shareUrl = facebook_share_url(
                $newsUrl,
                (string) ($news['title'] ?? ''),
                excerpt((string) ($news['body'] ?? ''))
              );
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
              $newsUrl = news_share_url($pageUrl, $newsNumber);
              $shareUrl = facebook_share_url($newsUrl, (string) $item['title'], excerpt((string) $item['body']));
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

    <section id="dokumentumok" class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Letöltések</p>
          <h2>Dokumentumok</h2>
        </div>
      </div>
      <?php if (!empty($documents)): ?>
        <div class="document-grid">
          <?php foreach ($documents as $document): ?>
            <article class="document-card">
              <div class="document-icon" aria-hidden="true">PDF</div>
              <div>
                <h3><?php echo e($document['title']); ?></h3>
                <p><?php echo e($document['filename']); ?> · <?php echo e($document['size']); ?></p>
              </div>
              <a class="button secondary dark" href="<?php echo e($document['url']); ?>" download>Letöltés</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty-state">A dokumentumok hamarosan felkerülnek.</p>
      <?php endif; ?>
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
