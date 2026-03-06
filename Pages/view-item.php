<?php
declare(strict_types=1);
session_start();


require_once __DIR__ . '/../includes/db.php';

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function badgeForStatus(string $status): array {
  // Updated to match your database status values
  if ($status === 'pending')    return ['Pending Claim', 'badge-pending'];
  if ($status === 'claimed')    return ['Claimed', 'badge-claimed'];
  if ($status === 'returned')   return ['Returned', 'badge-claimed'];
  return ['Recent', 'badge-recent'];
}

$pdo = db();

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($itemId <= 0) {
  http_response_code(400);
  $notFound = true;
} else {
  $notFound = false;
}

$claimSuccess = false;
$claimError = '';
$claimErrors = [];

$item = null;
$photos = [];
$idDetails = null;

if (!$notFound) {
  // Fetch item + joins - REMOVED offices table completely
  $stmt = $pdo->prepare("
    SELECT
      i.*,
      c.name AS category_name,
      l.name AS location_name
    FROM items i
    INNER JOIN categories c ON c.id = i.category_id
    INNER JOIN locations  l ON l.id = i.found_location_id
    WHERE i.id = :id
    LIMIT 1
  ");
  $stmt->execute([':id' => $itemId]);
  $item = $stmt->fetch();

  if (!$item) {
    $notFound = true;
  } else {
    // Photos
    $pstmt = $pdo->prepare("
      SELECT file_path
      FROM item_photos
      WHERE item_id = :id
      ORDER BY sort_order ASC, id ASC
    ");
    $pstmt->execute([':id' => $itemId]);
    $photos = $pstmt->fetchAll();

    // Optional: ID details if table exists + if category is IDs related
    try {
      $idCategories = ['Identification Cards', 'Bank Cards', 'IDs & Cards'];
      if (in_array($item['category_name'], $idCategories)) {
        // Check if item_id_details table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'item_id_details'");
        if ($checkTable->rowCount() > 0) {
          $dstmt = $pdo->prepare("
            SELECT id_type, name_on_id, department, distinct_feature
            FROM item_id_details
            WHERE item_id = :id
            LIMIT 1
          ");
          $dstmt->execute([':id' => $itemId]);
          $idDetails = $dstmt->fetch() ?: null;
        }
      }
    } catch (Throwable $e) {
      // If the table doesn't exist yet, ignore silently.
      $idDetails = null;
    }
  }
}

/* ---------------------------
   Claim request submission
---------------------------- */
if (!$notFound && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim') {
  
  // Check if user is logged in
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Please login to submit a claim request'));
    exit;
  }
  
  $contact = trim($_POST['contact'] ?? '');
  $proof = trim($_POST['proof'] ?? '');

  // Validation
  if ($contact === '') $claimErrors[] = "Please enter a contact number.";
  if ($proof === '' || mb_strlen($proof) < 12) $claimErrors[] = "Please add a short proof/description (at least 12 characters).";

  if (empty($claimErrors)) {
    try {
      $pdo->beginTransaction();

      // Get user details from session
      $user_id = $_SESSION['user_id'];
      $fullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
      $studentOrEmail = $_SESSION['student_id'] ?? $_SESSION['email'];

      // Insert claim request - matching your table structure
      $stmt = $pdo->prepare("
        INSERT INTO claim_requests (
          item_id, user_id, claimer_name, claimer_email, claimer_phone, proof_description, status
        ) VALUES (
          :item_id, :user_id, :claimer_name, :claimer_email, :claimer_phone, :proof_description, 'pending'
        )
      ");
      
      $result = $stmt->execute([
        ':item_id' => $itemId,
        ':user_id' => $user_id,
        ':claimer_name' => $fullName,
        ':claimer_email' => $studentOrEmail,
        ':claimer_phone' => $contact,
        ':proof_description' => $proof
      ]);

      if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Insert failed: " . $errorInfo[2]);
      }

      // Update item status to pending if currently recent
      if (($item['status'] ?? '') === 'recent') {
        $pdo->prepare("UPDATE items SET status = 'pending' WHERE id = :id")->execute([':id' => $itemId]);
        $item['status'] = 'pending';
      }

      $pdo->commit();

      // Redirect to avoid resubmission
      header("Location: view-item.php?id={$itemId}&claim=success");
      exit;

    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $claimError = "Could not submit claim request: " . $e->getMessage();
      // Log error for debugging
      error_log("Claim error: " . $e->getMessage());
    }
  }
}

$claimSuccess = (($_GET['claim'] ?? '') === 'success');
$justSubmitted = (($_GET['submitted'] ?? '') === '1');

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
  <section class="view-shell">
    <div class="container">

      <div class="view-topbar">
        <a class="back-link" href="find-my-item.php" aria-label="Back to Items Turned In">
          <span class="back-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="ico" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 18l-6-6 6-6"></path>
            </svg>
          </span>
          <span>Back to Items Turned In</span>
        </a>

        <?php if (!$notFound): ?>
          <?php [$badgeText, $badgeClass] = badgeForStatus((string)$item['status']); ?>
          <div class="status-stack" aria-label="Item status">
            <span class="badge <?= h($badgeClass) ?>"><?= h($badgeText) ?></span>
            <span class="pill"><?= h((string)$item['category_name']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($notFound): ?>
        <header class="view-header">
          <h1>Item not found</h1>
          <p class="view-subtext">The item you are looking for may have been removed or the link is incorrect.</p>
        </header>

        <div class="panel">
          <p class="notes-text">Go back to the items list and try again.</p>
          <div class="divider"></div>
          <a class="back-link" href="find-my-item.php">
            <span class="back-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" class="ico" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"></path>
              </svg>
            </span>
            <span>Back to Items Turned In</span>
          </a>
        </div>

      <?php else: ?>

        <header class="view-header">
          <h1><?= h((string)$item['title']) ?></h1>
          <p class="view-subtext">
            Items listed here are held by campus offices for safekeeping. Claiming requires verification to protect owners.
          </p>
        </header>

        <?php if ($justSubmitted): ?>
          <div class="panel" style="border-color: rgba(214,158,46,0.28); background: rgba(255,255,255,0.95);">
            <strong>Report submitted.</strong>
            <p class="notes-text" style="margin-top:6px; color: var(--text-muted);">
              Your item report has been saved. You can share this page with staff if needed.
            </p>
          </div>
          <div style="height: 12px;"></div>
        <?php endif; ?>

        <?php if ($claimSuccess): ?>
          <div class="panel" style="border-color: rgba(43,108,176,0.18); background: rgba(255,255,255,0.95);">
            <strong>Claim request submitted.</strong>
            <p class="notes-text" style="margin-top:6px; color: var(--text-muted);">
              A staff member will review your request and follow up with instructions once verified.
            </p>
          </div>
          <div style="height: 12px;"></div>
        <?php endif; ?>

        <div class="view-layout">

          <aside class="view-media" aria-label="Item photos">
  <?php
    // Prepare photo list for JS - FIXED PATH
    $jsPhotos = [];
    if (count($photos) > 0) {
      foreach ($photos as $p) {
        // Use the path exactly as stored in database
        $jsPhotos[] = $p['file_path'];
      }
    } else {
       // Placeholder if no photos
       $jsPhotos[] = 'data:image/svg+xml;utf8,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="720">
          <rect width="100%" height="100%" fill="#EDF2F7"/>
          <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"
            font-family="Inter, Arial" font-size="28" fill="#718096">No photo</text>
        </svg>'
      );
    }
    $mainPhoto = $jsPhotos[0];
  ?>

  <div class="media-card">
    <div class="main-photo-wrapper">
      <img
        id="mainPhoto"
        class="media-main"
        src="<?= h($mainPhoto) ?>"
        alt="Photo of <?= h((string)$item['title']) ?>"
        title="<?= h((string)$item['title']) ?>"
        onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'960\' height=\'720\'><rect width=\'100%\' height=\'100%\' fill=\'#EDF2F7\'/><text x=\'50%\' y=\'50%\' text-anchor=\'middle\' dominant-baseline=\'middle\' font-family=\'Inter, Arial\' font-size=\'28\' fill=\'#718096\'>Image not found</text></svg>') ?>';"
      />
      <div class="zoom-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="11" y1="8" x2="11" y2="14"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
      </div>
    </div>
  </div>

  <?php if (count($photos) > 1): ?>
    <div class="media-thumbs" aria-label="More photos">
      <?php foreach ($photos as $idx => $p): ?>
        <button class="thumb <?= $idx === 0 ? 'is-active' : '' ?>"
                type="button"
                data-idx="<?= $idx ?>"
                data-src="<?= h($p['file_path']) ?>"
                aria-label="View photo <?= (int)($idx + 1) ?>">
          <img src="<?= h($p['file_path']) ?>" alt="" />
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</aside>

          <aside class="view-side">

            <section class="panel" aria-label="Item details">
              <div class="panel-title-row">
                <h2>Item Details</h2>
                <span class="helper">For identification only</span>
              </div>

              <div class="facts">
                <div class="fact">
                  <span class="fact-label">Found at</span>
                  <span class="fact-value">
                    <?= h((string)$item['location_name']) ?>
                    <?php if (!empty($item['found_at_detail'])): ?>
                      — <?= h((string)$item['found_at_detail']) ?>
                    <?php endif; ?>
                  </span>
                </div>

                <div class="fact">
                  <span class="fact-label">Date found</span>
                  <span class="fact-value"><?= h(date('M d, Y', strtotime((string)$item['found_date']))) ?></span>
                </div>

                <div class="fact">
                  <span class="fact-label">Status</span>
                  <span class="fact-value">
                    <?php 
                      $statusText = [
                        'recent' => 'Recently Found',
                        'pending' => 'Pending Claim',
                        'claimed' => 'Claimed',
                        'returned' => 'Returned to Owner'
                      ];
                      echo h($statusText[$item['status']] ?? $item['status']);
                    ?>
                  </span>
                </div>
              </div>

              <?php if ($idDetails): ?>
                <div class="divider"></div>
                <div class="notes-label">ID details</div>
                <div class="facts">
                  <div class="fact">
                    <span class="fact-label">ID type</span>
                    <span class="fact-value"><?= h((string)$idDetails['id_type']) ?></span>
                  </div>
                  <div class="fact">
                    <span class="fact-label">Name visible</span>
                    <span class="fact-value"><?= h((string)$idDetails['name_on_id']) ?></span>
                  </div>
                  <?php if (!empty($idDetails['department'])): ?>
                    <div class="fact">
                      <span class="fact-label">Department</span>
                      <span class="fact-value"><?= h((string)$idDetails['department']) ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($idDetails['distinct_feature'])): ?>
                    <div class="fact">
                      <span class="fact-label">Distinct feature</span>
                      <span class="fact-value"><?= h((string)$idDetails['distinct_feature']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($item['description'])): ?>
                <div class="divider"></div>
                <div class="notes-label">Description</div>
                <p class="notes-text"><?= nl2br(h((string)$item['description'])) ?></p>
              <?php endif; ?>
              
              <?php if (!empty($item['contact_email']) || !empty($item['contact_phone'])): ?>
                <div class="divider"></div>
                <div class="notes-label">Contact Information</div>
                <p class="notes-text">
                  <?php if (!empty($item['contact_email'])): ?>
                    <strong>Email:</strong> <?= h($item['contact_email']) ?><br>
                  <?php endif; ?>
                  <?php if (!empty($item['contact_phone'])): ?>
                    <strong>Phone:</strong> <?= h($item['contact_phone']) ?>
                  <?php endif; ?>
                </p>
              <?php endif; ?>
            </section>

            <div class="notice-card" aria-label="Verification notice">
              <div class="notice-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="ico" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                  <path d="M9 12l2 2 4-4"></path>
                </svg>
              </div>
              <div class="notice-text">
                <strong>Verification protects owners.</strong>
                <p>Claims are reviewed by staff before pickup instructions are provided.</p>
              </div>
            </div>

            <?php if ($item['status'] === 'recent' || $item['status'] === 'pending'): ?>
<section class="panel panel-claim" aria-label="Claim request">
  <div class="panel-title-row">
    <h2>Claim Request</h2>
    <span class="helper">Staff verified</span>
  </div>

  <?php if ($claimError): ?>
    <div class="panel" style="padding:12px; border-color: rgba(155,44,44,0.20); background: rgba(155,44,44,0.06);">
      <strong><?= h($claimError) ?></strong>
    </div>
    <div style="height: 10px;"></div>
  <?php endif; ?>

  <?php if (!empty($claimErrors)): ?>
    <div class="panel" style="padding:12px; border-color: rgba(155,44,44,0.20); background: rgba(155,44,44,0.06);">
      <strong>Please fix the following:</strong>
      <ul style="margin:8px 0 0 18px;">
        <?php foreach ($claimErrors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div style="height: 10px;"></div>
  <?php endif; ?>

  <details class="claim-details">
    <summary class="claim-summary">
      <div class="summary-left">
        <div class="summary-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" class="ico" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 1l3 5 5 .7-3.7 3.6.9 5.1L12 13.8 7.8 15.4l.9-5.1L5 6.7 10 6z"></path>
          </svg>
        </div>
        <div>
          <strong>Submit a claim request</strong>
          <small>We'll ask a few details to verify ownership.</small>
        </div>
      </div>
      <div class="summary-arrow" aria-hidden="true">
        <svg viewBox="0 0 24 24" class="ico" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 9l6 6 6-6"></path>
        </svg>
      </div>
    </summary>

    <?php if (!isset($_SESSION['user_id'])): ?>
      <!-- Not logged in - Show login prompt -->
      <div class="login-prompt" style="padding: 30px 20px; text-align: center; background: #f8f9fa; border-radius: 8px; margin-top: 16px;">
        <div style="font-size: 48px; margin-bottom: 16px;">🔒</div>
        <h3 style="margin-bottom: 12px; color: #333;">Login Required</h3>
        <p style="margin-bottom: 20px; color: #666;">Please log in to submit a claim request for this item.</p>
        <a href="../Login/login.php?redirect=<?= urlencode('view-item.php?id=' . $itemId) ?>" class="btn btn-primary" style="padding: 12px 24px;">Log In to Claim</a>
        <p style="margin-top: 16px; font-size: 14px;">
          Don't have an account? <a href="register.php" style="color: #9B2C2C;">Register here</a>
        </p>
      </div>
    <?php else: ?>
      <!-- Logged in - Show simplified claim form -->
      <?php
      // Get user's phone from database
      $userStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
      $userStmt->execute([$_SESSION['user_id']]);
      $userPhone = $userStmt->fetchColumn();
      ?>
      
      <form class="claim-form" method="post" novalidate style="margin-top: 20px;">
        <input type="hidden" name="action" value="claim">
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
        
        <!-- Auto-filled user info (read-only) -->
        <div class="form-grid" style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
          <div class="field" style="margin-bottom: 12px;">
            <label style="font-weight: 600; color: #555;">Your Name</label>
            <div style="padding: 10px 12px; background: white; border: 1px solid #ddd; border-radius: 8px;">
              <?= h($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
            </div>
          </div>
          
          <div class="field" style="margin-bottom: 12px;">
            <label style="font-weight: 600; color: #555;">Student ID / Email</label>
            <div style="padding: 10px 12px; background: white; border: 1px solid #ddd; border-radius: 8px;">
              <?= h($_SESSION['student_id'] ?? $_SESSION['email']) ?>
            </div>
          </div>
          
          <div class="field">
            <label for="contact" style="font-weight: 600; color: #555;">Contact Number</label>
            <input 
              type="text" 
              name="contact" 
              id="contact" 
              value="<?= h($userPhone ?? '') ?>" 
              placeholder="09XX XXX XXXX" 
              required
              style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;"
            >
            <p class="field-hint" style="font-size: 13px; color: #666; margin-top: 4px;">Your contact is visible to staff only.</p>
          </div>
        </div>

        <!-- Only ask for proof/description -->
        <div class="field">
          <label for="proof" style="font-weight: 600; color: #555;">Proof / Description <span style="color: #9B2C2C;">*</span></label>
          <textarea 
            name="proof" 
            id="proof" 
            rows="4" 
            placeholder="Describe a detail only the owner would know (stickers, contents, scratches, etc.)." 
            required
            style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"
          ><?= h($_POST['proof'] ?? '') ?></textarea>
          <p class="field-hint" style="font-size: 13px; color: #666; margin-top: 4px;">Do not share sensitive numbers (ID numbers, addresses).</p>
        </div>

        <div class="field">
          <label for="where_lost" style="font-weight: 600; color: #555;">Where did you lose it? (optional)</label>
          <input 
            type="text" 
            name="where_lost" 
            id="where_lost" 
            value="<?= h($_POST['where_lost'] ?? '') ?>" 
            placeholder="e.g., SU Main Library, 2nd floor"
            style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;"
          >
        </div>

        <div class="claim-actions" style="display: flex; gap: 12px; margin-top: 24px;">
          <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Submit Claim Request</button>
          <a class="btn btn-muted" href="find-my-item.php" style="padding: 12px 24px; background: #f0f0f0; color: #666; border-radius: 8px; text-decoration: none;">Cancel</a>
        </div>

        <p class="fineprint" style="font-size: 13px; color: #999; margin-top: 16px; text-align: center;">
          Once verified, staff will provide the official pickup instructions.
        </p>
      </form>
    <?php endif; ?>
  </details>
</section>
<?php else: ?>
<section class="panel" style="background: rgba(155,44,44,0.04);">
  <div class="panel-title-row">
    <h2>Claim Status</h2>
  </div>
  <p class="notes-text">
    This item is <?= $item['status'] === 'claimed' ? 'already claimed' : 'no longer available for claiming' ?>.
  </p>
</section>
<?php endif; ?>

          </aside>
        </div>

      <?php endif; ?>
    </div>
  </section>
</main>

<div id="lightbox" class="lightbox-overlay" aria-hidden="true">
  <button class="lightbox-close" aria-label="Close full view">&times;</button>
  
  <button class="lightbox-nav prev" aria-label="Previous image">
    <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none">
      <path d="M15 18l-6-6 6-6"></path>
    </svg>
  </button>
  
  <div class="lightbox-content">
    <img id="lightboxImg" src="" alt="Full view">
  </div>

  <button class="lightbox-nav next" aria-label="Next image">
    <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none">
      <path d="M9 18l6-6-6-6"></path>
    </svg>
  </button>
</div>

<script>
  // Gallery logic + Lightbox
  (function(){
    // Pass PHP photos array to JS
    const allPhotos = <?= json_encode($jsPhotos) ?>;
    let currentIndex = 0;

    const main = document.getElementById('mainPhoto');
    const thumbs = document.querySelectorAll('.thumb');
    
    // Lightbox elements
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const closeBtn = document.querySelector('.lightbox-close');
    const prevBtn = document.querySelector('.lightbox-nav.prev');
    const nextBtn = document.querySelector('.lightbox-nav.next');

    // -- Thumbnail Interaction --
    if (thumbs.length > 0) {
      thumbs.forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = parseInt(btn.dataset.idx);
          updateMainImage(idx);
        });
      });
    }

    function updateMainImage(index) {
      currentIndex = index;
      // Update main image
      main.src = allPhotos[currentIndex];
      // Update active state of thumbnails
      thumbs.forEach(b => b.classList.remove('is-active'));
      if(thumbs[currentIndex]) thumbs[currentIndex].classList.add('is-active');
    }

    // -- Lightbox Logic --
    function openLightbox() {
      if (allPhotos.length === 0) return;
      lightboxImg.src = allPhotos[currentIndex];
      lightbox.classList.add('active');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
      updateNavVisibility();
    }

    function closeLightbox() {
      lightbox.classList.remove('active');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = ''; // Restore scrolling
    }

    function showNext() {
      currentIndex = (currentIndex + 1) % allPhotos.length;
      lightboxImg.src = allPhotos[currentIndex];
      updateMainImage(currentIndex); // Sync background too
    }

    function showPrev() {
      currentIndex = (currentIndex - 1 + allPhotos.length) % allPhotos.length;
      lightboxImg.src = allPhotos[currentIndex];
      updateMainImage(currentIndex);
    }

    function updateNavVisibility() {
      // If only 1 photo, hide arrows
      if (allPhotos.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
      } else {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
      }
    }

    // Events
    if (main) main.addEventListener('click', openLightbox);
    
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (prevBtn) prevBtn.addEventListener('click', (e) => { e.stopPropagation(); showPrev(); });
    if (nextBtn) nextBtn.addEventListener('click', (e) => { e.stopPropagation(); showNext(); });

    // Close on background click
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) closeLightbox();
    });

    // Keyboard support
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('active')) return;
      
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') showPrev();
      if (e.key === 'ArrowRight') showNext();
    });

  })();
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>