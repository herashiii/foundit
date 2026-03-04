<?php
// view-item.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Placeholder logic fallback */
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

$pdo = db();

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$notFound = false;
$item = null;
$photos = [];
$idDetails = null;

if ($itemId <= 0) {
    http_response_code(400);
    $notFound = true;
} else {
    // Fetch item + joins strictly using db schema
    $stmt = $pdo->prepare("
        SELECT
            i.*,
            c.name AS category_name,
            l.name AS location_name,
            o.name AS office_name,
            o.location AS office_location
        FROM items i
        LEFT JOIN categories c ON c.id = i.category_id
        LEFT JOIN locations l ON l.id = i.found_location_id
        LEFT JOIN offices o ON o.id = i.office_id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $notFound = true;
    } else {
        // Fetch photos using id ordering to match the schema safely
        $stmtPhotos = $pdo->prepare("SELECT file_path FROM item_photos WHERE item_id = ? ORDER BY id ASC");
        $stmtPhotos->execute([$itemId]);
        $fetchedPhotos = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);
        
       // Smart Path Resolver (Pages/view-item.php + Pages/uploads/...)
        foreach ($fetchedPhotos as $photoPath) {
            $photoPath = trim((string)$photoPath);
            if ($photoPath === '') continue;

            // Normalize (remove leading slash)
            $clean = ltrim($photoPath, '/');

            // Because uploads/ is inside Pages/, this is the correct disk path:
            $diskPath = __DIR__ . '/' . $clean;

            if (file_exists($diskPath)) {
                // Web path relative to Pages/
                $photos[] = $clean;
            }
        }
        if (empty($photos)) {
            $photos[] = placeholderDataUri($item['title']);
        }

        // Fetch ID details if they exist
        $stmtId = $pdo->prepare("SELECT * FROM item_id_details WHERE item_id = ?");
        $stmtId->execute([$itemId]);
        $idDetails = $stmtId->fetch(PDO::FETCH_ASSOC);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="page-container">
    <!-- Breadcrumbs -->
    <div class="container">
        <nav class="breadcrumb" aria-label="Secondary Navigation">
            <a href="index.php">Home</a>
            <span class="sep" aria-hidden="true">/</span>
            <a href="find-my-item.php">Items Turned In</a>
            <span class="sep" aria-hidden="true">/</span>
            <span class="active"><?= $notFound ? 'Not Found' : h($item['title']) ?></span>
        </nav>
    </div>

    <section class="container main-section">
        <?php if ($notFound): ?>
            <div class="alert alert-error" role="alert">
                <span class="icon" aria-hidden="true">⚠</span>
                <div>
                    <strong>Item Not Found</strong><br>
                    The item you are looking for does not exist or has been removed.
                </div>
            </div>
            <a href="find-my-item.php" class="btn btn-secondary" style="margin-top: 24px;">Return to Browse</a>
        <?php else: ?>
            
            <div class="view-layout">
                <!-- LEFT COLUMN: Photo Gallery -->
                <div class="gallery-column">
                    <?php 
                        $mainPhoto = !empty($photos) ? $photos[0] : placeholderDataUri($item['title']); 
                    ?>
                    
                    <div class="main-photo-container">
                        <img src="<?= h($mainPhoto) ?>" 
                             alt="Main photo of <?= h($item['title']) ?>" 
                             title="Click to expand image"
                             id="mainImage" 
                             class="main-photo clickable">
                        
                        <?php if($item['status'] === 'recent'): ?>
                            <span class="badge badge-recent">Recent</span>
                        <?php elseif($item['status'] === 'pending_claim'): ?>
                            <span class="badge badge-pending">Pending Claim</span>
                        <?php else: ?>
                            <span class="badge badge-claimed">Claimed</span>
                        <?php endif; ?>
                    </div>

                    <?php if (count($photos) > 1): ?>
                        <div class="thumbnail-grid" id="thumbnailList">
                            <?php foreach ($photos as $index => $photoUrl): ?>
                                <button type="button" class="thumb-btn <?= $index === 0 ? 'active' : '' ?>" 
                                        data-index="<?= $index ?>" 
                                        aria-label="View photo <?= $index + 1 ?>"
                                        title="View photo <?= $index + 1 ?>">
                                    <img src="<?= h($photoUrl) ?>" alt="Thumbnail <?= $index + 1 ?> of <?= h($item['title']) ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT COLUMN: Details & Actions -->
                <div class="details-column">
                    
                    <!-- System Message (Informational Blue Wash) -->
                    <div class="alert alert-info">
                        <span class="icon" aria-hidden="true">ℹ</span>
                        <p>Review the photos and details carefully. If you believe this is yours, proceed to claim it.</p>
                    </div>

                    <!-- Item Header -->
                    <div class="item-header">
                        <span class="category-pill"><?= h($item['category_name'] ?? 'General') ?></span>
                        <h1 class="item-title"><?= h($item['title']) ?></h1>
                        <p class="report-date">Reported on <?= date('F j, Y, g:i a', strtotime($item['created_at'])) ?></p>
                    </div>

                    <!-- Meta Data -->
                    <div class="meta-box">
                        <div class="meta-row" title="The campus location where this was found">
                            <span class="meta-icon" aria-hidden="true">📍</span>
                            <div class="meta-content">
                                <span class="meta-label">Found At</span>
                                <span class="meta-value"><?= h($item['location_name'] ?? 'Unspecified') ?></span>
                                <?php if (!empty($item['specific_location'])): ?>
                                    <span class="meta-subtext">(<?= h($item['specific_location']) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="meta-row" title="When the finder picked up the item">
                            <span class="meta-icon" aria-hidden="true">📅</span>
                            <div class="meta-content">
                                <span class="meta-label">Date Found</span>
                                <span class="meta-value"><?= date('F j, Y', strtotime($item['found_date'])) ?></span>
                                <?php if (!empty($item['time_of_day'])): ?>
                                    <span class="meta-subtext">(<?= h(ucfirst($item['time_of_day'])) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="meta-row" title="The office currently holding this item">
                            <span class="meta-icon" aria-hidden="true">🏢</span>
                            <div class="meta-content">
                                <span class="meta-label">Current Custody</span>
                                <span class="meta-value"><?= h($item['office_name'] ?? 'With Finder') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Documentation Content (Calibri 12pt as per rules) -->
                    <div class="documentation-content">
                        <h2>Description</h2>
                        <p class="doc-text"><?= nl2br(h($item['description'] ?? 'No additional description provided.')) ?></p>

                        <?php if ($idDetails): ?>
                            <h2 style="margin-top: 24px;">ID Card Details</h2>
                            <ul class="doc-list">
                                <li><strong>Name:</strong> <?= h($idDetails['owner_name'] ?? 'N/A') ?></li>
                                <li><strong>ID Number:</strong> <?= h($idDetails['id_number'] ?? 'N/A') ?></li>
                                <li><strong>Dept/College:</strong> <?= h($idDetails['department_college'] ?? 'N/A') ?></li>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Call To Action Panel -->
                    <div class="cta-panel">
                        <?php if ($item['status'] === 'recent'): ?>
                            <h3 class="cta-title">Is this yours?</h3>
                            <p class="cta-desc">You will need to provide specific details not visible in the photos to prove ownership.</p>
                            <div class="cta-actions">
                                <a href="claim-item.php?id=<?= $item['id'] ?>" class="btn-primary" title="Proceed to the claim verification form">
                                    Proceed to Claim
                                </a>
                            </div>
                        <?php elseif ($item['status'] === 'pending_claim'): ?>
                            <div class="alert alert-warning" style="margin-bottom: 0;">
                                <span class="icon" aria-hidden="true">⏳</span>
                                <div>
                                    <strong>Claim Pending</strong><br>
                                    Someone has submitted a claim for this item. It is currently awaiting staff verification.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-bottom: 0;">
                                <span class="icon" aria-hidden="true">✔</span>
                                <div>
                                    <strong>Item Claimed</strong><br>
                                    This item has been successfully returned to its owner.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox" role="dialog" aria-label="Image gallery" aria-modal="true" hidden>
    <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close gallery" title="Close">×</button>
    <button type="button" class="lightbox-nav prev" id="lightboxPrev" aria-label="Previous photo" title="Previous">❮</button>
    <div class="lightbox-content">
        <img id="lightboxImg" src="" alt="Full size view">
    </div>
    <button type="button" class="lightbox-nav next" id="lightboxNext" aria-label="Next photo" title="Next">❯</button>
</div>

<script>
    // Vanilla JS Lightbox & Thumbnail Logic
    document.addEventListener('DOMContentLoaded', () => {
        const allPhotos = <?= json_encode(!empty($photos) ? $photos : [$mainPhoto]) ?>;
        if (allPhotos.length === 0) return;

        let currentIndex = 0;
        
        const mainImage = document.getElementById('mainImage');
        const thumbBtns = document.querySelectorAll('.thumb-btn');
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightboxImg');
        const btnPrev = document.getElementById('lightboxPrev');
        const btnNext = document.getElementById('lightboxNext');
        const btnClose = document.getElementById('lightboxClose');

        // Update Main Image from Thumbnail
        thumbBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                currentIndex = parseInt(e.currentTarget.getAttribute('data-index'));
                mainImage.src = allPhotos[currentIndex];
                
                thumbBtns.forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        });

        // Lightbox Controls
        const openLightbox = () => {
            lightboxImg.src = allPhotos[currentIndex];
            lightbox.removeAttribute('hidden');
            lightbox.classList.add('active');
            if(btnClose) btnClose.focus();
        };

        const closeLightbox = () => {
            lightbox.classList.remove('active');
            setTimeout(() => lightbox.setAttribute('hidden', 'true'), 300);
            if(mainImage) mainImage.focus();
        };

        const showNext = () => {
            currentIndex = (currentIndex + 1) % allPhotos.length;
            lightboxImg.src = allPhotos[currentIndex];
            updateThumbnails();
        };

        const showPrev = () => {
            currentIndex = (currentIndex - 1 + allPhotos.length) % allPhotos.length;
            lightboxImg.src = allPhotos[currentIndex];
            updateThumbnails();
        };

        const updateThumbnails = () => {
            if(mainImage) mainImage.src = allPhotos[currentIndex];
            thumbBtns.forEach(b => b.classList.remove('active'));
            if(thumbBtns[currentIndex]) thumbBtns[currentIndex].classList.add('active');
        };

        if(mainImage) mainImage.addEventListener('click', openLightbox);
        if(btnClose) btnClose.addEventListener('click', closeLightbox);
        if(btnNext) btnNext.addEventListener('click', (e) => { e.stopPropagation(); showNext(); });
        if(btnPrev) btnPrev.addEventListener('click', (e) => { e.stopPropagation(); showPrev(); });

        // Hide arrows if only 1 photo
        if (allPhotos.length <= 1) {
            if(btnNext) btnNext.style.display = 'none';
            if(btnPrev) btnPrev.style.display = 'none';
        }

        // Close on bg click
        if(lightbox) {
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) closeLightbox();
            });
        }

        // Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox || !lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') showNext();
            if (e.key === 'ArrowLeft') showPrev();
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>