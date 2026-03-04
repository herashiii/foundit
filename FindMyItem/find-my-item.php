<?php
require_once __DIR__ . '/includes/db.php';
include 'includes/header.php';

/** Escape helper */
function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Read filters (GET) */
$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$locationId = (int)($_GET['location_id'] ?? 0);
$status = trim($_GET['status'] ?? '');        // recent | pending_claim | claimed
$dateRange = trim($_GET['date'] ?? '');       // today | week | older
$sort = trim($_GET['sort'] ?? 'newest');      // newest | oldest

// Fetch categories for chips
$categories = db()->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Fetch locations for dropdown
$locations = db()->query("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

/** Build query */
$where = [];
$params = [];

// Search (title/description/category/location)
if ($q !== '') {
  $where[] = "(i.title LIKE :q_title OR i.description LIKE :q_desc OR c.name LIKE :q_cat OR l.name LIKE :q_loc)";
  $like = "%{$q}%";
  $params[':q_title'] = $like;
  $params[':q_desc']  = $like;
  $params[':q_cat']   = $like;
  $params[':q_loc']   = $like;
}


// Category
if ($categoryId > 0) {
  $where[] = "i.category_id = :category_id";
  $params[':category_id'] = $categoryId;
}

// Location
if ($locationId > 0) {
  $where[] = "i.found_location_id = :location_id";
  $params[':location_id'] = $locationId;
}

// Status
$allowedStatus = ['recent', 'pending_claim', 'claimed'];
if ($status !== '' && in_array($status, $allowedStatus, true)) {
  $where[] = "i.status = :status";
  $params[':status'] = $status;
}

// Date range (based on found_date)
if ($dateRange === 'today') {
  $where[] = "i.found_date = CURDATE()";
} elseif ($dateRange === 'week') {
  $where[] = "i.found_date >= (CURDATE() - INTERVAL 7 DAY)";
} elseif ($dateRange === 'older') {
  $where[] = "i.found_date < (CURDATE() - INTERVAL 7 DAY)";
}

$orderBy = "i.found_date DESC, i.created_at DESC";
if ($sort === 'oldest') {
  $orderBy = "i.found_date ASC, i.created_at ASC";
}

$sql = "
  SELECT
    i.id,
    i.title,
    i.status,
    i.found_at_detail,
    i.found_date,
    i.found_time,
    i.created_at,
    c.name AS category_name,
    l.name AS location_name,
    (
      SELECT p.file_path
      FROM item_photos p
      WHERE p.item_id = i.id
      ORDER BY p.sort_order ASC, p.id ASC
      LIMIT 1
    ) AS photo_path
  FROM items i
  INNER JOIN categories c ON c.id = i.category_id
  INNER JOIN locations  l ON l.id = i.found_location_id
";

// Add WHERE if needed
if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY {$orderBy} LIMIT 100";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$count = count($items);

/** Badge mapping */
function badgeForStatus(string $status): array {
  // Your CSS expects: badge-recent | badge-pending | badge-claimed
  if ($status === 'pending_claim') return ['Pending Claim', 'badge-pending'];
  if ($status === 'claimed')       return ['Claimed', 'badge-claimed'];
  return ['Recent', 'badge-recent'];
}

/** Safe placeholder image (no external URL needed) */
function placeholderDataUri(string $label = 'Item'): string {
  $label = preg_replace('/[^a-zA-Z0-9 \-]/', '', $label);
  $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="560" height="420">
  <rect width="100%" height="100%" fill="#EDF2F7"/>
  <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"
        font-family="Inter, Arial" font-size="28" fill="#718096">$label</text>
</svg>
SVG;
  return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
?>

<link rel="stylesheet" href="css/find-my-item.css">

<main class="main-content">
  <section class="find-shell">
    <div class="container">

      <!-- Title + reassurance -->
      <header class="find-header">
        <h1>Items Turned In</h1>
        <p class="find-subtext">
          Items listed here have been turned in to campus offices for safekeeping and verification.
        </p>
      </header>

      <!-- Search + Filters (NOW REAL) -->
      <form class="find-tools" method="get" aria-label="Search and filters">
        <div class="find-search">
          <div class="search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M21 21l-4.3-4.3"></path>
            </svg>
          </div>

          <input
            type="text"
            name="q"
            class="find-input"
            value="<?= h($q) ?>"
            placeholder="Try: ID card, keys, umbrella, calculator, blue tumbler…"
            aria-label="Search turned-in items"
          />

          <button class="find-search-btn btn btn-primary" type="submit" aria-label="Search">
            Search
          </button>
        </div>

        <div class="find-filters" aria-label="Filters">
          <?php
            // “All” chip clears category_id
            $allChipClass = ($categoryId === 0) ? 'chip chip-active' : 'chip';
            $baseQuery = $_GET;
            unset($baseQuery['category_id']);
            $allHref = '?' . http_build_query($baseQuery);
          ?>
          <a class="<?= $allChipClass ?>" href="<?= h($allHref) ?>">All</a>

          <?php foreach ($categories as $cat): ?>
            <?php
              $isActive = ($categoryId === (int)$cat['id']);
              $chipClass = $isActive ? 'chip chip-active' : 'chip';

              $q2 = $_GET;
              $q2['category_id'] = (int)$cat['id'];
              $href = '?' . http_build_query($q2);
            ?>
            <a class="<?= $chipClass ?>" href="<?= h($href) ?>"><?= h($cat['name']) ?></a>
          <?php endforeach; ?>

          <div class="filter-selects">
            <label class="select-wrap">
              <span class="sr-only">Location</span>
              <select class="select" name="location_id" aria-label="Filter by location" onchange="this.form.submit()">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= (int)$loc['id'] ?>" <?= $locationId === (int)$loc['id'] ? 'selected' : '' ?>>
                    <?= h($loc['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="select-wrap">
              <span class="sr-only">Date</span>
              <select class="select" name="date" aria-label="Filter by date" onchange="this.form.submit()">
                <option value="" <?= $dateRange === '' ? 'selected' : '' ?>>Any Date</option>
                <option value="today" <?= $dateRange === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= $dateRange === 'week' ? 'selected' : '' ?>>This Week</option>
                <option value="older" <?= $dateRange === 'older' ? 'selected' : '' ?>>Older</option>
              </select>
            </label>

            <label class="select-wrap">
              <span class="sr-only">Status</span>
              <select class="select" name="status" aria-label="Filter by status" onchange="this.form.submit()">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>Any Status</option>
                <option value="recent" <?= $status === 'recent' ? 'selected' : '' ?>>Recent</option>
                <option value="pending_claim" <?= $status === 'pending_claim' ? 'selected' : '' ?>>Pending Claim</option>
                <option value="claimed" <?= $status === 'claimed' ? 'selected' : '' ?>>Claimed</option>
              </select>
            </label>

            <!-- Keep existing sort UI but now real -->
            <input type="hidden" name="sort" value="<?= h($sort) ?>">
          </div>
        </div>
      </form>

      <!-- Results header -->
      <div class="results-bar" aria-label="Results">
        <div class="results-meta">
          <span class="results-count"><strong><?= (int)$count ?></strong> items shown</span>
          <span class="results-hint">Click an item to view details and verify ownership.</span>
        </div>

        <div class="results-sort">
          <label class="sr-only" for="sort">Sort</label>
          <select id="sort" class="select" aria-label="Sort results"
                  onchange="window.location = updateQueryString('sort', this.value);">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
          </select>
        </div>
      </div>

      <!-- Results grid -->
      <?php if ($count > 0): ?>
        <div class="items-grid" aria-label="Turned-in items list">
          <?php foreach ($items as $it): ?>
            <?php
              [$badgeText, $badgeClass] = badgeForStatus($it['status']);

              $img = $it['photo_path']
                ? h($it['photo_path'])
                : placeholderDataUri($it['category_name']);

              // Location display: location + optional detail
              $locationText = $it['location_name'];
              if (!empty($it['found_at_detail'])) {
                $locationText .= ' — ' . $it['found_at_detail'];
              }

              // “Turned in” uses created_at (until you add a dedicated turned_in column)
              $turnedIn = date('M d, Y', strtotime($it['created_at']));
            ?>

            <a class="item-card"
               href="view-item.php?id=<?= (int)$it['id'] ?>"
               aria-label="View item: <?= h($it['title']) ?>">

              <img class="item-img" src="<?= $img ?>" alt="<?= h($it['title']) ?>">

              <div class="item-body">
                <div class="item-top">
                  <span class="badge <?= h($badgeClass) ?>"><?= h($badgeText) ?></span>
                  <span class="pill"><?= h($it['category_name']) ?></span>
                </div>

                <h3 class="item-title"><?= h($it['title']) ?></h3>

                <div class="item-meta">
                  <div class="meta-row">
                    <span class="meta-label">FOUND AT</span>
                    <span class="meta-value"><?= h($locationText) ?></span>
                  </div>
                  <div class="meta-row">
                    <span class="meta-label">DATE FOUND</span>
                    <span class="meta-value"><?= h($turnedIn) ?></span>
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h2>No matching items yet</h2>
          <p>If your item is not listed, it may not have been turned in yet. You can file a report so we can help match it.</p>
          <a href="turn-in-item.php" class="btn btn-primary">Turn In Item</a>
        </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<script>
  // Small helper for changing sort without losing other filters
  function updateQueryString(key, value){
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    return url.toString();
  }
</script>

<?php include 'includes/footer.php'; ?>
