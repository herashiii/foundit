<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// Helper for safe HTML
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Placeholder logic fallback */
function placeholderDataUri(string $label = 'Item'): string {
    $label = preg_replace('/[^a-zA-Z0-9 \-]/', '', $label);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
  <rect width="100%" height="100%" fill="#F1F5F9"/>
  <g transform="translate(200 130)">
    <rect x="-42" y="-22" width="84" height="50" rx="10" fill="#E2E8F0"/>
    <path d="M-22 -22 L-12 -40 H12 L22 -22" fill="none" stroke="#94A3B8" stroke-width="6" stroke-linejoin="round"/>
    <rect x="-18" y="-6" width="36" height="8" rx="4" fill="#CBD5E1"/>
  </g>
  <text x="50%" y="74%" text-anchor="middle"
        font-family="Inter, Arial" font-size="20" fill="#94A3B8">$label</text>
</svg>
SVG;
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

$pdo = db();

// Fetch dynamic categories and their available item counts
$catStmt = $pdo->query("
    SELECT c.id, c.name, COUNT(i.id) as count
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id AND i.status IN ('unclaimed', 'pending')
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $catStmt->fetchAll();

// Process GET filters
$searchQuery = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$freshness = $_GET['f'] ?? ['recent', 'unclaimed'];

// Base query conditions
$where = ["i.status IN ('unclaimed', 'pending')"];
$params = [];

if ($searchQuery !== '') {
    $where[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($catFilter > 0) {
    $where[] = "i.category_id = ?";
    $params[] = $catFilter;
}

// 24-hour freshness checkbox logic
$isRecent = in_array('recent', (array)$freshness, true);
$isUnclaimed = in_array('unclaimed', (array)$freshness, true);

if ($isRecent && !$isUnclaimed) {
    $where[] = "(i.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) OR i.status = 'pending')";
} elseif (!$isRecent && $isUnclaimed) {
    $where[] = "(i.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) OR i.status = 'pending')";
}

$whereClause = implode(' AND ', $where);

// Sorting
$sortParam = $_GET['sort'] ?? 'newest';
$orderBy = $sortParam === 'oldest' ? 'i.created_at ASC' : 'i.created_at DESC';

// Fetch items
$itemsStmt = $pdo->prepare("
    SELECT
        i.id,
        i.title,
        i.created_at,
        i.found_date,
        i.status,
        c.name as category,
        l.name as location,
        (
            SELECT file_path
            FROM item_photos p
            WHERE p.item_id = i.id
            ORDER BY p.id ASC
            LIMIT 1
        ) as photo
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN locations l ON i.found_location_id = l.id
    WHERE $whereClause
    ORDER BY $orderBy
");
$itemsStmt->execute($params);
$items = $itemsStmt->fetchAll();
?>

<main class="portal-main-wrapper">
    <section class="portal-header-strip">
        <div class="container">
            <nav class="breadcrumb" aria-label="Secondary Navigation">
                <a href="index.php">Home</a>
                <span class="sep" aria-hidden="true">/</span>
                <span class="active">Find My Item</span>
            </nav>
            <div class="header-content">
                <h1>Find My Item</h1>
                <p>Search and browse items found across campus grounds.</p>
            </div>
        </div>
    </section>

    <section class="portal-layout container">
        <aside class="portal-sidebar">
            <form id="filterForm" method="GET" action="find-my-item.php">
                <div class="sidebar-section">
                    <h2 class="sidebar-title">Search</h2>
                    <div class="search-box">
                        <img src="../magnifying-glass-search.png" class="search-signifier" alt="" aria-hidden="true">
                        <input type="text" name="q" value="<?= h($searchQuery) ?>" placeholder="Type here..." title="Search by item name or description">
                    </div>
                </div>

                <div class="sidebar-section">
                    <h2 class="sidebar-title">Availability</h2>
                    <label class="filter-option">
                        <input type="checkbox" name="f[]" value="recent" <?= $isRecent ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span>Recent (Last 24h)</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="f[]" value="unclaimed" <?= $isUnclaimed ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span>Unclaimed</span>
                    </label>
                </div>

                <div class="sidebar-section">
                    <h2 class="sidebar-title">Categories</h2>
                    <ul class="category-list">
                        <?php
                            $qParam = $searchQuery ? '&q=' . urlencode($searchQuery) : '';
                            $fParam = '';
                            foreach ((array)$freshness as $f) {
                                $fParam .= '&f[]=' . urlencode($f);
                            }
                        ?>
                        <li class="<?= $catFilter === 0 ? 'active' : '' ?>">
                            <a href="?cat=0<?= $qParam ?><?= $fParam ?>">All Categories</a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li class="<?= $catFilter === (int)$cat['id'] ? 'active' : '' ?>">
                                <a href="?cat=<?= (int)$cat['id'] ?><?= $qParam ?><?= $fParam ?>">
                                    <?= h($cat['name']) ?>
                                    <span class="count"><?= (int)$cat['count'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <input type="hidden" name="sort" value="<?= h($sortParam) ?>">
            </form>
        </aside>

        <div class="portal-main">
            <div class="system-msg msg-info">
                <span class="icon">ℹ</span>
                <p>Showing all items currently held at university offices. Click an item to view verification requirements.</p>
            </div>

            <?php if (count($items) === 0): ?>
                <div class="system-msg" style="background: var(--neut-gray-1); border: 1px dashed var(--neut-gray-4); justify-content: center; padding: 40px; text-align: center;">
                    <p style="color: var(--split-char-2); font-weight: 500;">No items found matching your filters.</p>
                </div>
            <?php else: ?>
                <div class="grid-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <span style="font-size: 0.9rem; color: var(--split-char-2);">Showing <strong><?= count($items) ?></strong> items</span>

                    <select onchange="document.querySelector('input[name=\'sort\']').value=this.value; document.getElementById('filterForm').submit();" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--neut-gray-3); font-family: 'Inter', sans-serif;">
                        <option value="newest" <?= $sortParam === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sortParam === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>

                <div class="items-grid">
                    <?php foreach ($items as $item):
                        $createdTime = strtotime($item['created_at']);
                        $isRecentItem = (time() - $createdTime) <= (24 * 3600);

                        if ($item['status'] === 'pending') {
                            $badgeText = 'Pending';
                            $badgeClass = 'pending';
                        } elseif ($item['status'] === 'unclaimed' && $isRecentItem) {
                            $badgeText = 'Recent';
                            $badgeClass = 'recent';
                        } else {
                            $badgeText = 'Unclaimed';
                            $badgeClass = 'unclaimed';
                        }

                        // Working image resolver for Pages/find-my-item.php + Pages/uploads/...
                        $photoPath = trim((string)($item['photo'] ?? ''));

                        if ($photoPath !== '') {
                            $cleanPath = ltrim($photoPath, '/');
                            $diskPath = __DIR__ . '/' . $cleanPath;

                            if (file_exists($diskPath)) {
                                $imgSrc = h($cleanPath);
                            } else {
                                $imgSrc = placeholderDataUri($item['title']);
                            }
                        } else {
                            $imgSrc = placeholderDataUri($item['title']);
                        }

                        $location = !empty($item['location']) ? $item['location'] : 'Unspecified Location';
                        $dateFormatted = !empty($item['found_date'])
                            ? date('M d, Y', strtotime($item['found_date']))
                            : 'Unknown';
                    ?>
                        <a href="view-item.php?id=<?= (int)$item['id'] ?>" class="mini-card" style="text-decoration: none;">
                            <div class="card-media">
                                <img src="<?= $imgSrc ?>"
                                     alt="Photo of <?= h($item['title']) ?>"
                                     title="Click to view details for <?= h($item['title']) ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='<?= placeholderDataUri($item['title']) ?>';">

                                <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>

                            <div class="card-body">
                                <span class="category-pill"><?= h($item['category'] ?? 'General') ?></span>
                                <h3 class="card-title"><?= h($item['title']) ?></h3>

                                <div class="card-meta">
                                    <div class="meta-row" title="Specific location where found">
                                        <span class="meta-icon" aria-hidden="true">📍</span>
                                        <span class="meta-label">FOUND AT:</span>
                                        <span class="meta-value"><?= h($location) ?></span>
                                    </div>

                                    <div class="meta-row" title="The date this item was turned in">
                                        <span class="meta-icon" aria-hidden="true">📅</span>
                                        <span class="meta-label">DATE FOUND:</span>
                                        <span class="meta-value"><?= h($dateFormatted) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>