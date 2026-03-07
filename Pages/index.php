<?php 
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// Helper for safe HTML output
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helper for placeholder image
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

// Fetch 3 most recent items
$pdo = db();
$stmt = $pdo->prepare("
    SELECT 
        i.id, 
        i.title, 
        i.found_date, 
        i.found_at_detail,
        c.name AS category_name, 
        l.name AS location_name,
        (SELECT file_path FROM item_photos WHERE item_id = i.id ORDER BY sort_order ASC LIMIT 1) AS photo_path
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.found_location_id = l.id
    WHERE i.status IN ('unclaimed', 'pending')
    ORDER BY i.found_date DESC, i.created_at DESC
    LIMIT 3
");
$stmt->execute();
$recentItems = $stmt->fetchAll();
?>

<header class="hero">
    <div class="container">
        <div class="hero-content">

            <h1>Lost something on campus?</h1>
            <p>Don't panic. Search the turned-in items registry to look for your lost item.</p>
            
            <form action="find-my-item.php" method="GET" class="search-bar">
                <input type="text" name="q" class="search-input" placeholder="Search for items (e.g. 'Silver MacBook')...">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <div class="hero-pills">
                <span class="pills-label">Quick Filters:</span>
                <a href="find-my-item.php?category=Identification%20Cards" class="hero-pill">IDs & Cards</a>
                <a href="find-my-item.php?category=Keys" class="hero-pill">Keys</a>
                <a href="find-my-item.php?category=Electronics" class="hero-pill">Electronics</a>
                <a href="find-my-item.php?category=Books" class="hero-pill">Books</a>
            </div>
        </div>
    </div>
</header>

<main class="main-content">
    
    <section class="section-cta container">
    <div class="cta-grid">

        <div class="action-card action-card--lost">
            <div class="action-card-top">

                <div class="action-card-head">
                    <div class="action-title-row">
                        <h3>I Lost Something</h3>
                    </div>
                    <p class="action-card-desc">
                        Check the real-time list of items currently held. You can filter by location and category to spot your belonging.
                    </p>
                </div>
            </div>

            <ul class="card-bullets" aria-label="What you can expect">
                <li>Items are posted immediately on turn-in</li>
                <li>Filter results by date, location, or type</li>
                <li>Easy verification to claim</li>
            </ul>

            <div class="action-card-actions">
                <a href="find-my-item.php" class="action-btn action-btn--lost" aria-label="Browse Turned In Items">
                    <span class="action-btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="action-btn-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M21 21l-4.3-4.3"></path>
                        </svg>
                    </span>

                    <span class="action-btn-text">
                        <strong>Browse Turned In Items</strong>
                        <small>Search the registry for matches</small>
                    </span>

                    <span class="action-btn-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="action-btn-arrow-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>
                        </svg>
                    </span>
                </a>
            </div>
        </div>

        <div class="action-card action-card--found">
            <div class="action-card-top">

                <div class="action-card-head">
                    <div class="action-title-row">
                        <h3>I Found Something</h3>
                    </div>
                    <p class="action-card-desc">
                        Turn in or report a found item so the owner can claim it through the proper process.
                    </p>
                </div>
            </div>

            <ul class="card-bullets" aria-label="How claiming works">
                <li>Quick posting with clear item details</li>
                <li>Claiming is reviewed before release</li>
                <li>Helps prevent fraudulent pickups</li>
            </ul>

            <div class="action-card-actions">
                <a href="turn-in-item.php" class="action-btn action-btn--found" aria-label="Turn In a Found Item">
                    <span class="action-btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="action-btn-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8l-9-5-9 5 9 5 9-5z"></path>
                            <path d="M3 8v10l9 5 9-5V8"></path>
                            <path d="M12 13v10"></path>
                        </svg>
                    </span>

                    <span class="action-btn-text">
                        <strong>Turn In a Found Item</strong>
                        <small>Help return an item to its owner</small>
                    </span>

                    <span class="action-btn-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="action-btn-arrow-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>
                        </svg>
                    </span>
                </a>
            </div>
        </div>

    </div>
    </section>

    <section class="section-recent container">
        <div class="section-header">
            <div>
            <h2>Recently Found Items</h2>
            <p class="section-subtitle">The most recent turned-in items to the registry.</p>
            </div>
            <a href="find-my-item.php" class="section-link">View All →</a>
        </div>

        <div class="recent-grid">
            <?php if (count($recentItems) > 0): ?>
                <?php foreach ($recentItems as $item): ?>
                    <?php
                        $img = $item['photo_path'] ? h($item['photo_path']) : placeholderDataUri($item['category_name']);
                        $dateFound = date('M d, Y', strtotime($item['found_date']));
                        $locDisplay = h($item['location_name']);
                    ?>
                    
                    <a href="view-item.php?id=<?= (int)$item['id'] ?>" class="mini-card" aria-label="View item: <?= h($item['title']) ?>">
                        <img src="<?= $img ?>" alt="<?= h($item['title']) ?>" class="mini-card-img" title="<?= h($item['title']) ?>">
                        <div class="mini-card-body">
                            <div class="mini-card-top">
                                <span class="status-badge found">Found</span>
                                <span class="category-pill"><?= h($item['category_name']) ?></span>
                            </div>

                            <h3 class="mini-card-title"><?= h($item['title']) ?></h3>

                            <div class="mini-card-meta-grid" role="list">
                                <div class="meta-item" role="listitem">
                                    <span class="meta-label">Found at</span>
                                    <span class="meta-value"><?= $locDisplay ?></span>
                                </div>
                                <div class="meta-item" role="listitem">
                                    <span class="meta-label">Date found</span>
                                    <span class="meta-value"><?= $dateFound ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <p>No recently found items reported yet.</p>
                </div>
            <?php endif; ?>
        </div>
        </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>