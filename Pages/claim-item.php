<?php
// claim-item.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Simulate logged-in user for demonstration (In production, enforce authentication)
$currentUserId = $_SESSION['user_id'] ?? 1; 

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
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
        SELECT i.id, i.title, i.status, c.name AS category_name, o.name AS office_name 
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN offices o ON i.office_id = o.id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

        if (!$item) {
            $errorMsg = 'Item not found.';
        } elseif ($item['status'] !== 'available' && $item['status'] !== 'recent') {
            $errorMsg = 'This item is no longer available for claims.';
        } else {
            // FIX: Changed 'is_primary DESC' to 'sort_order ASC' to match your database schema
            $photoStmt = $pdo->prepare("SELECT file_path FROM item_photos WHERE item_id = ? ORDER BY sort_order ASC LIMIT 1");
            $photoStmt->execute([$itemId]);
            $photoRow = $photoStmt->fetch();
            
            // Ensure path resolution matches your folder structure
            if ($photoRow && !empty($photoRow['file_path'])) {

                $clean = ltrim(trim((string)$photoRow['file_path']), '/'); // uploads/items/...
                $disk  = __DIR__ . '/' . $clean;                            // Pages/uploads/items/...

                if (file_exists($disk)) {
                    $itemPhoto = $clean; // correct path
                } else {
                    $itemPhoto = placeholderDataUri($item['title']);
                }

            } else {
                $itemPhoto = placeholderDataUri($item['title']);
            }        }
} else {
    $errorMsg = 'Invalid item request.';
}

// Handle Form Submission
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errorMsg) {
    $proof = trim($_POST['proof_description'] ?? '');
    
    if (strlen($proof) < 20) {
        $errorMsg = 'Please provide a more detailed description (at least 20 characters) to prove ownership.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Fetch current user info to fill claim_requests (since the table lacks a user_id)
            $userStmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
            $userStmt->execute([$currentUserId]);
            $userData = $userStmt->fetch();
            $fullName = $userData['first_name'] . ' ' . $userData['last_name'];

            // 2. Insert claim request using columns that actually exist in your SQL schema
            $insertClaim = $pdo->prepare("
                INSERT INTO claim_requests 
                (item_id, claimer_name, claimer_email, claimer_phone, proof_description, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $insertClaim->execute([
                $itemId, 
                $fullName, 
                $userData['email'], 
                $userData['phone'], 
                $proof
            ]);
            
            // 3. Update item status to 'pending' (must match ENUM in your SQL dump)
            $updateItem = $pdo->prepare("UPDATE items SET status = 'pending' WHERE id = ?");
            $updateItem->execute([$itemId]);
            
            $pdo->commit();
            $successMsg = 'Your claim request has been submitted successfully. Please proceed to the designated office with your ID.';
        } catch (Exception $e) {
            $pdo->rollBack();
            // For debugging, you can temporary change this to: $errorMsg = $e->getMessage();
            $errorMsg = 'A system error occurred while processing your request. Please try again.';
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
            <span class="active">Claim Request</span>
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
                    <span class="icon" aria-hidden="true">⚠</span>
                    <span><?= h($errorMsg) ?></span>
                </div>
                <div class="form-actions" style="margin-top: 24px;">
                    <a href="find-my-item.php" class="btn btn-secondary">Return to Browse</a>
                </div>
            <?php elseif ($successMsg): ?>
                <div class="alert alert-success" role="alert">
                    <span class="icon" aria-hidden="true">✔</span>
                    <div>
                        <strong>Claim Submitted!</strong><br>
                        <?= h($successMsg) ?>
                    </div>
                </div>
                <div class="form-actions" style="margin-top: 24px;">
                    <a href="dashboard.php" class="btn btn-primary">Go to My Portal</a>
                </div>
            <?php else: ?>

                <!-- Context Card -->
                <div class="item-context-card">
                    <img src="<?= h($itemPhoto) ?>" 
                         alt="Image of <?= h($item['title']) ?>" 
                         title="Item being claimed: <?= h($item['title']) ?>" 
                         class="context-img">
                    <div class="context-details">
                        <span class="context-category"><?= h($item['category_name']) ?></span>
                        <h2 class="context-title"><?= h($item['title']) ?></h2>
                        <div class="context-meta" title="Current location of the item">
                            <span aria-hidden="true">📍</span> Held at: <?= h($item['office_name'] ?? 'Not specified') ?>
                        </div>
                    </div>
                </div>

                <!-- Instructional Feedback -->
                <div class="alert alert-info">
                    <span class="icon" aria-hidden="true">ℹ</span>
                    <p><strong>What to include:</strong> Describe unique features not visible in the photo. (e.g., scratches, lock screen wallpaper, specific contents, or serial numbers).</p>
                </div>

                <!-- Claim Form -->
                <form method="POST" action="claim-item.php?id=<?= $itemId ?>">
                    <div class="form-group">
                        <label for="proof_description">Proof of Ownership <span class="required" aria-hidden="true">*</span></label>
                        <textarea id="proof_description" name="proof_description" rows="5" placeholder="I can prove this is mine because..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="view-item.php?id=<?= $itemId ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit Claim</button>
                    </div>
                </form>

            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>