<?php
// claim-item.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CHECK IF USER IS LOGGED IN FIRST - HCI Friendly
if (!isset($_SESSION['user_id'])) {
    // Store the item ID to redirect back after login
    $_SESSION['redirect_after_login'] = 'claim-item.php?id=' . ($_GET['id'] ?? '');
    header('Location: ../Login/login.php?error=' . urlencode('Please log in to submit a claim request'));
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Get logged-in user ID from session
$currentUserId = $_SESSION['user_id'];

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helper function for placeholder image
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

$pdo = db();
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;
$itemPhoto = null;
$errorMsg = '';
$successMsg = '';

// Fetch Item Details & Validation
if ($itemId > 0) {
    $stmt = $pdo->prepare("
        SELECT i.id, i.title, i.status, i.user_id as owner_id, c.name AS category_name, o.name AS office_name 
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN offices o ON i.office_id = o.id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        $errorMsg = 'Item not found.';
    } elseif ($item['status'] !== 'recent' && $item['status'] !== 'pending') {
        $errorMsg = 'This item is no longer available for claims.';
    } elseif ($item['owner_id'] == $currentUserId) {
        // HCI: Prevent users from claiming their own items
        $errorMsg = 'You cannot claim an item that you reported.';
    } else {
        // Get item photo
        $photoStmt = $pdo->prepare("SELECT file_path FROM item_photos WHERE item_id = ? ORDER BY sort_order ASC LIMIT 1");
        $photoStmt->execute([$itemId]);
        $photoRow = $photoStmt->fetch();
        
        if ($photoRow && !empty($photoRow['file_path'])) {
            $clean = ltrim(trim((string)$photoRow['file_path']), '/');
            $disk  = __DIR__ . '/' . $clean;
            
            if (file_exists($disk)) {
                $itemPhoto = $clean;
            } else {
                $itemPhoto = placeholderDataUri($item['title']);
            }
        } else {
            $itemPhoto = placeholderDataUri($item['title']);
        }
    }
} else {
    $errorMsg = 'Invalid item request.';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errorMsg) {
    // Verify user is still logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../Login/login.php?error=' . urlencode('Your session has expired. Please log in again.'));
        exit;
    }
    
    $proof = trim($_POST['proof_description'] ?? '');
    
    if (strlen($proof) < 20) {
        $errorMsg = 'Please provide a more detailed description (at least 20 characters) to prove ownership.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Fetch current user info
            $userStmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
            $userStmt->execute([$currentUserId]);
            $userData = $userStmt->fetch();
            
            if (!$userData) {
                throw new Exception('User data not found.');
            }
            
            $fullName = $userData['first_name'] . ' ' . $userData['last_name'];

            // 2. Check if user already has a pending claim for this item
            $checkStmt = $pdo->prepare("
                SELECT id FROM claim_requests 
                WHERE item_id = ? AND claimer_email = ? AND status = 'pending'
            ");
            $checkStmt->execute([$itemId, $userData['email']]);
            
            if ($checkStmt->fetch()) {
                throw new Exception('You already have a pending claim for this item.');
            }

            // 3. Insert claim request
            $insertClaim = $pdo->prepare("
                INSERT INTO claim_requests 
                (item_id, user_id, claimer_name, claimer_email, claimer_phone, proof_description, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $insertClaim->execute([
                $itemId, 
                $currentUserId,
                $fullName, 
                $userData['email'], 
                $userData['phone'] ?? 'Not provided', 
                $proof
            ]);
            
            // 4. Update item status to 'pending'
            $updateItem = $pdo->prepare("UPDATE items SET status = 'pending' WHERE id = ?");
            $updateItem->execute([$itemId]);
            
            $pdo->commit();
            $successMsg = 'Your claim request has been submitted successfully. Please wait for staff verification.';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = $e->getMessage();
            error_log("Claim error: " . $e->getMessage());
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="page-container">
    <!-- Secondary Navigation -->
    <div class="container">
        <nav class="breadcrumb" aria-label="Secondary Navigation">
            <a href="index.php">Home</a>
            <span class="sep" aria-hidden="true">/</span>
            <a href="find-my-item.php">Items Turned In</a>
            <span class="sep" aria-hidden="true">/</span>
            <?php if ($item): ?>
                <a href="view-item.php?id=<?= $item['id'] ?>"><?= h($item['title']) ?></a>
                <span class="sep" aria-hidden="true">/</span>
            <?php endif; ?>
            <span class="active" aria-current="page">Claim Request</span>
        </nav>
    </div>

    <!-- Main Content -->
    <section class="container form-section">
        <div class="form-wrapper">
            
            <header class="form-header">
                <h1>Submit Claim Request</h1>
                <p>Provide specific details to verify your ownership of this item.</p>
            </header>

            <?php if ($errorMsg): ?>
                <div class="alert alert-error" role="alert">
                    <span class="icon" aria-hidden="true">⚠️</span>
                    <div>
                        <strong>Unable to Process Request</strong><br>
                        <?= h($errorMsg) ?>
                    </div>
                </div>
                <div class="form-actions" style="margin-top: 24px;">
                    <a href="find-my-item.php" class="btn btn-secondary">Browse Items</a>
                    <a href="view-item.php?id=<?= $itemId ?>" class="btn btn-primary">Back to Item</a>
                </div>
                
            <?php elseif ($successMsg): ?>
                <div class="alert alert-success" role="status">
                    <span class="icon" aria-hidden="true">✅</span>
                    <div>
                        <strong>Claim Submitted Successfully!</strong><br>
                        <?= h($successMsg) ?>
                    </div>
                </div>
                
                <!-- Next steps guidance -->
                <div class="item-context-card" style="margin-top: 24px; background: var(--neut-gray-1);">
                    <div class="context-details" style="width: 100%;">
                        <span class="context-category">What happens next?</span>
                        <ul style="margin: 16px 0 0 20px; color: var(--split-char-2); line-height: 1.8;">
                            <li>Staff will review your claim within 24-48 hours</li>
                            <li>You'll receive an email notification once verified</li>
                            <li>Bring your valid ID to the designated office for pickup</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 24px;">
                    <a href="dashboard.php" class="btn btn-primary">View My Dashboard</a>
                    <a href="find-my-item.php" class="btn btn-secondary">Browse More Items</a>
                </div>
                
            <?php else: ?>

                <!-- User Info Summary (HCI: Show user they're logged in) -->
                <div class="item-context-card" style="margin-bottom: 20px; background: var(--neut-gray-1);">
                    <div class="context-details" style="width: 100%;">
                        <span class="context-category">Claiming as</span>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <div style="background: var(--mono-red-3); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= h($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></strong><br>
                                <small style="color: var(--split-char-2);">Your information will be automatically included.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Context Card -->
                <div class="item-context-card">
                    <img src="<?= h($itemPhoto) ?>" 
                         alt="Image of <?= h($item['title']) ?>" 
                         title="Item being claimed: <?= h($item['title']) ?>" 
                         class="context-img"
                         onerror="this.src='<?= placeholderDataUri($item['title']) ?>'">
                    <div class="context-details">
                        <span class="context-category"><?= h($item['category_name']) ?></span>
                        <h2 class="context-title"><?= h($item['title']) ?></h2>
                        <div class="context-meta" title="Current location of the item">
                            <span aria-hidden="true">📍</span> 
                            <span>Held at: <?= h($item['office_name'] ?? 'Campus Office') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Instructional Feedback - HCI Friendly -->
                <div class="alert alert-info" role="note">
                    <span class="icon" aria-hidden="true">ℹ️</span>
                    <div>
                        <strong>Tips for a successful claim:</strong>
                        <ul style="margin-top: 8px; margin-left: 20px;">
                            <li>Describe unique features not visible in the photo</li>
                            <li>Mention specific scratches, stickers, or contents</li>
                            <li>Include serial numbers if applicable</li>
                            <li>Be honest - false claims are recorded</li>
                        </ul>
                    </div>
                </div>

                <!-- Claim Form -->
                <form method="POST" action="claim-item.php?id=<?= $itemId ?>" id="claimForm">
                    <div class="form-group">
                        <label for="proof_description">
                            Proof of Ownership <span class="required" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        <textarea 
                            id="proof_description" 
                            name="proof_description" 
                            rows="5" 
                            placeholder="Example: 'My laptop has a small scratch on the bottom right corner and a sticker of a cat on the lid. The charger has a black tape around the cable.'" 
                            required
                            aria-describedby="proof-hint"
                            minlength="20"
                        ><?= h($_POST['proof_description'] ?? '') ?></textarea>
                        <div id="proof-hint" class="field-hint" style="font-size: 0.85rem; color: var(--split-char-2); margin-top: 4px;">
                            Minimum 20 characters. Be as specific as possible.
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="view-item.php?id=<?= $itemId ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="btn-text">Submit Claim</span>
                            <span class="btn-loading" style="display: none;">Submitting...</span>
                        </button>
                    </div>
                </form>

            <?php endif; ?>
        </div>
    </section>
</main>

<script>
// Form loading state - HCI friendly feedback
document.getElementById('claimForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoading = btn.querySelector('.btn-loading');
    
    if (this.checkValidity()) {
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
    }
});

// Character counter for proof description
document.getElementById('proof_description')?.addEventListener('input', function() {
    const minLength = 20;
    const currentLength = this.value.length;
    const hint = document.getElementById('proof-hint');
    
    if (currentLength < minLength) {
        hint.innerHTML = `⚠️ ${currentLength}/${minLength} characters. Please add more detail.`;
        hint.style.color = 'var(--mono-red-3)';
    } else {
        hint.innerHTML = `✅ ${currentLength}/${minLength} characters - Good detail!`;
        hint.style.color = 'var(--comp-green-3)';
    }
});
</script>

<style>
/* Additional HCI-friendly styles that complement your existing CSS */
.field-hint {
    transition: color 0.2s ease;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
}

.btn-loading::after {
    content: '';
    display: inline-block;
    width: 16px;
    height: 16px;
    margin-left: 8px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Ensure proper spacing */
.form-actions {
    margin-top: 24px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .item-context-card {
        flex-direction: column;
        text-align: center;
    }
    
    .context-img {
        margin-bottom: 12px;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>