<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// Helper for safe HTML
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Fetch categories for the sidebar
$categories = db()->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Mock counts (In production, use a JOIN or GROUP BY query)
$categoryCounts = [1 => 12, 2 => 5, 3 => 8, 10 => 3]; 

/** Placeholder logic */
function placeholderDataUri(string $label = 'Item'): string {
  $label = preg_replace('/[^a-zA-Z0-9 \-]/', '', $label);
  $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
  <rect width="100%" height="100%" fill="#F1F5F9"/>
  <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"
        font-family="Inter, Arial" font-size="20" fill="#94A3B8">$label</text>
</svg>
SVG;
  return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
?>

<main class="main-content">
    <!-- 1. Breadcrumbs & Header -->
    <section class="portal-header-strip">
        <div class="container">
            <nav class="breadcrumb" aria-label="Secondary Navigation">
                <a href="index.php">Home</a>
                <span class="sep" aria-hidden="true">/</span>
                <span class="active">Items Turned In</span>
            </nav>
            <div class="header-content">
                <h1>Items Turned In</h1>
                <p>Search and browse items found across campus grounds.</p>
            </div>
        </div>
    </section>

    <section class="portal-layout container">
        <!-- 2. Sidebar Filters -->
        <aside class="portal-sidebar">
            <form id="filterForm" method="GET">
                <div class="sidebar-section">
                    <h2 class="sidebar-title">Search</h2>
                    <div class="search-box">
                        <img src="../magnifying-glass-search.png" class="search-signifier" alt="" aria-hidden="true">
                        <input type="text" name="q" placeholder="Type here..." title="Search by item name or description">
                    </div>
                </div>

                <div class="sidebar-section">
                    <h2 class="sidebar-title">Availability</h2>
                    <label class="filter-option">
                        <input type="checkbox" name="status[]" value="recent">
                        <span>Recent (Last 24h)</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="status[]" value="unclaimed" checked>
                        <span>Unclaimed</span>
                    </label>
                </div>

                <div class="sidebar-section">
                    <h2 class="sidebar-title">Categories</h2>
                    <ul class="category-list">
                        <li class="active"><a href="?cat=0">All Categories</a></li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="?cat=<?= (int)$cat['id'] ?>">
                                    <?= h($cat['name']) ?>
                                    <span class="count"><?= $categoryCounts[$cat['id']] ?? 0 ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </form>
        </aside>

        <!-- 3. Results Grid -->
        <div class="portal-main">
            <!-- System Feedback -->
            <div class="system-msg msg-info">
                <span class="icon">ℹ</span>
                <p>Showing all items currently held at university offices. Click an item to view verification requirements.</p>
            </div>

            <div class="items-grid">
                <!-- Example Card 1 -->
                <article class="mini-card">
                    <div class="card-media">
                        <img src="<?= placeholderDataUri('MacBook Pro') ?>" 
                             alt="Silver MacBook Pro found in the library" 
                             title="Click to view details for Silver MacBook Pro">
                        <span class="status-badge recent">Recent</span>
                    </div>
                    <div class="card-body">
                        <span class="category-pill">Electronics</span>
                        <h3 class="card-title">Silver MacBook Pro</h3>
                        <div class="card-meta">
                            <div class="meta-row" title="Specific location where found">
                                <span class="meta-icon" aria-hidden="true">📍</span>
                                <span class="meta-label">At:</span>
                                <span class="meta-value">Main Library, 2nd Flr</span>
                            </div>
                            <div class="meta-row" title="The date this item was turned in">
                                <span class="meta-icon" aria-hidden="true">📅</span>
                                <span class="meta-label">Found:</span>
                                <span class="meta-value">Mar 04, 2026</span>
                            </div>
                        </div>
                        <a href="view-item.php?id=1" class="btn btn-primary card-action">View</a>
                    </div>
                </article>

                <!-- Example Card 2 -->
                <article class="mini-card">
                    <div class="card-media">
                        <img src="<?= placeholderDataUri('Silliman ID') ?>" 
                             alt="Student ID card found at the gym" 
                             title="Click to view details for Student ID Card">
                        <span class="status-badge">Unclaimed</span>
                    </div>
                    <div class="card-body">
                        <span class="category-pill">IDs</span>
                        <h3 class="card-title">Student ID Card</h3>
                        <div class="card-meta">
                            <div class="meta-row" title="Specific location where found">
                                <span class="meta-icon" aria-hidden="true">📍</span>
                                <span class="meta-label">At:</span>
                                <span class="meta-value">SU Gymnasium Entrance</span>
                            </div>
                            <div class="meta-row" title="The date this item was turned in">
                                <span class="meta-icon" aria-hidden="true">📅</span>
                                <span class="meta-label">Found:</span>
                                <span class="meta-value">Feb 28, 2026</span>
                            </div>
                        </div>
                        <a href="view-item.php?id=2" class="btn btn-primary card-action">View</a>
                    </div>
                </article>

                <!-- Example Card 3 -->
                <article class="mini-card">
                    <div class="card-media">
                        <img src="<?= placeholderDataUri('House Keys') ?>" 
                             alt="A bunch of keys with a blue keychain" 
                             title="Click to view details for House Keys">
                        <span class="status-badge">Unclaimed</span>
                    </div>
                    <div class="card-body">
                        <span class="category-pill">Keys</span>
                        <h3 class="card-title">Bundle of Keys</h3>
                        <div class="card-meta">
                            <div class="meta-row" title="Specific location where found">
                                <span class="meta-icon" aria-hidden="true">📍</span>
                                <span class="meta-label">At:</span>
                                <span class="meta-value">Arts & Sciences Quad</span>
                            </div>
                            <div class="meta-row" title="The date this item was turned in">
                                <span class="meta-icon" aria-hidden="true">📅</span>
                                <span class="meta-label">Found:</span>
                                <span class="meta-value">Mar 01, 2026</span>
                            </div>
                        </div>
                        <a href="view-item.php?id=3" class="btn btn-primary card-action">View</a>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>