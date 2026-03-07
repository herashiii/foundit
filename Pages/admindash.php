<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../Login/login.php?error=' . urlencode('Unauthorized access. Admin privileges required.'));
    exit;
}

require_once __DIR__ . '/../includes/db.php';

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helper function for ITEM status badges - matches your DB schema
function getItemStatusBadge($status) {
    $badges = [
        'unclaimed' => 'badge-recent',
        'pending' => 'badge-pending',
        'claimed' => 'badge-claimed'
    ];
    return $badges[$status] ?? 'badge-default';
}

// Helper function for CLAIM status badges
function getClaimStatusBadge($status) {
    $badges = [
        'pending' => 'badge-pending',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected'
    ];
    return $badges[$status] ?? 'badge-default';
}

$pdo = db();
$adminId = $_SESSION['user_id'];

// Get admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: ../Login/login.php?error=' . urlencode('Admin account not found.'));
    exit;
}

// Handle actions
$actionMessage = '';
$actionError = '';

// Approve/Reject Claim
if (isset($_GET['action']) && isset($_GET['claim_id'])) {
    $claimId = (int)$_GET['claim_id'];
    $action = $_GET['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        try {
            $pdo->beginTransaction();
            
            // Get claim details
            $stmt = $pdo->prepare("SELECT * FROM claim_requests WHERE id = ?");
            $stmt->execute([$claimId]);
            $claim = $stmt->fetch();
            
            if ($claim) {
                // Update claim status
                $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
                $updateStmt = $pdo->prepare("UPDATE claim_requests SET status = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $claimId]);
                
                // If approved, update item status to 'claimed'
                if ($action === 'approve') {
                    $itemStmt = $pdo->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
                    $itemStmt->execute([$claim['item_id']]);
                }
                
                $pdo->commit();
                $actionMessage = "Claim #$claimId has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
                
                // Redirect to remove query parameters
                header('Location: admindash.php?message=' . urlencode($actionMessage));
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $actionError = "Error processing claim: " . $e->getMessage();
        }
    }
}

// Handle message from redirect
if (isset($_GET['message'])) {
    $actionMessage = $_GET['message'];
}

// Update Item Status
if (isset($_POST['update_item_status']) && isset($_POST['item_id']) && isset($_POST['new_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];
    
    // Updated to match your DB schema - only unclaimed, pending, claimed
    $allowedStatuses = ['unclaimed', 'pending', 'claimed'];
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $itemId]);
            $actionMessage = "Item #$itemId status updated to '$newStatus'.";
            
            // Redirect to remove POST data
            header('Location: admindash.php?message=' . urlencode($actionMessage));
            exit;
        } catch (Exception $e) {
            $actionError = "Error updating item: " . $e->getMessage();
        }
    }
}

// Toggle User Active Status
if (isset($_GET['toggle_user']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = $stmt->fetchColumn();
        
        $newStatus = $current ? 0 : 1;
        $updateStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $userId]);
        
        $actionMessage = "User #$userId has been " . ($newStatus ? 'activated' : 'deactivated') . ".";
        
        // Redirect to remove query parameters
        header('Location: admindash.php?message=' . urlencode($actionMessage));
        exit;
    } catch (Exception $e) {
        $actionError = "Error updating user: " . $e->getMessage();
    }
}

// Get statistics - Updated to match your DB schema
$stats = [];
$stats['total_items'] = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$stats['unclaimed_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'unclaimed'")->fetchColumn();
$stats['pending_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn();
$stats['claimed_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'claimed'")->fetchColumn();

$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['admin_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$stats['student_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

$stats['total_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests")->fetchColumn();
$stats['pending_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'pending'")->fetchColumn();
$stats['approved_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'approved'")->fetchColumn();
$stats['rejected_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'rejected'")->fetchColumn();

// Get ALL pending claims
$pendingClaims = $pdo->query("
    SELECT cr.*, i.title as item_title, i.status as item_status, i.user_id as item_owner_id,
           u.first_name, u.last_name, u.email, u.student_id,
           owner.first_name as owner_first, owner.last_name as owner_last
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    LEFT JOIN users u ON cr.user_id = u.id
    LEFT JOIN users owner ON i.user_id = owner.id
    WHERE cr.status = 'pending'
    ORDER BY cr.created_at ASC
")->fetchAll();

// Get all claims
$allClaims = $pdo->query("
    SELECT cr.*, i.title as item_title, i.status as item_status, i.user_id as item_owner_id,
           u.first_name, u.last_name, u.email, u.student_id,
           owner.first_name as owner_first, owner.last_name as owner_last
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    LEFT JOIN users u ON cr.user_id = u.id
    LEFT JOIN users owner ON i.user_id = owner.id
    ORDER BY cr.created_at DESC
")->fetchAll();

// Get all items
$allItems = $pdo->query("
    SELECT i.*, c.name as category_name, l.name as location_name,
           u.first_name as reporter_first, u.last_name as reporter_last,
           (SELECT COUNT(*) FROM claim_requests WHERE item_id = i.id) as claim_count
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.found_location_id = l.id
    LEFT JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC
")->fetchAll();

// Get all users
$allUsers = $pdo->query("
    SELECT * FROM users 
    ORDER BY 
        CASE role 
            WHEN 'admin' THEN 1 
            WHEN 'user' THEN 2 
        END,
        created_at DESC
")->fetchAll();

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="css/admindash.css">

<main class="admin-dashboard">
    <div class="container">
        <!-- Admin Header -->
        <div class="admin-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <strong><?= h($admin['first_name'] . ' ' . $admin['last_name']) ?></strong> (Administrator)</p>
                <span class="admin-badge">
                    Last login: <?= date('M d, Y h:i A', strtotime($admin['created_at'] ?? 'now')) ?>
                </span>
            </div>
        </div>

        <!-- Action Messages -->
        <?php if ($actionMessage): ?>
            <div class="alert alert-success">
                <span class="icon">✅</span>
                <span><?= h($actionMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($actionError): ?>
            <div class="alert alert-error">
                <span class="icon">⚠️</span>
                <span><?= h($actionError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Stats Grid - Updated to match your schema -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_items'] ?></span>
                    <span class="stat-label">Total Items</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini">Unclaimed: <?= $stats['unclaimed_items'] ?></span>
                    <span class="stat-mini">Pending: <?= $stats['pending_items'] ?></span>
                    <span class="stat-mini">Claimed: <?= $stats['claimed_items'] ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_users'] ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini">Admins: <?= $stats['admin_users'] ?></span>
                    <span class="stat-mini">Students: <?= $stats['student_users'] ?></span>
                    <span class="stat-mini">Active: <?= $stats['active_users'] ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_claims'] ?></span>
                    <span class="stat-label">Total Claims</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini">Pending: <?= $stats['pending_claims'] ?></span>
                    <span class="stat-mini">Approved: <?= $stats['approved_claims'] ?></span>
                    <span class="stat-mini">Rejected: <?= $stats['rejected_claims'] ?></span>
                </div>
            </div>
        </div>

        <!-- Pending Claims Section (Priority) -->
        <?php if (count($pendingClaims) > 0): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>
                    <span>⚠️</span>
                    Pending Claims - Action Required
                </h2>
                <span class="section-badge"><?= count($pendingClaims) ?> pending</span>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Claimant</th>
                            <th>Contact</th>
                            <th>Proof</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingClaims as $claim): ?>
                        <tr class="pending-row">
                            <td>#<?= $claim['id'] ?></td>
                            <td>
                                <strong><?= h($claim['item_title']) ?></strong>
                                <br><small>Item Status: 
                                    <span class="status-badge <?= getItemStatusBadge($claim['item_status']) ?>">
                                        <?= ucfirst($claim['item_status']) ?>
                                    </span>
                                </small>
                                <br><small>Owner: <?= h($claim['owner_first'] ?? 'Unknown') ?></small>
                            </td>
                            <td>
                                <?= h($claim['first_name'] ?? 'Unknown') ?> <?= h($claim['last_name'] ?? '') ?>
                                <br><small><?= h($claim['student_id'] ?? $claim['email']) ?></small>
                            </td>
                            <td><?= h($claim['claimer_phone'] ?? 'N/A') ?></td>
                            <td>
                                <div class="proof-preview">
                                    <?= h(substr($claim['proof_description'] ?? '', 0, 50)) ?>...
                                    <button class="btn-view-proof" onclick="showProof(<?= $claim['id'] ?>)">View</button>
                                </div>
                                <div id="proof-<?= $claim['id'] ?>" class="proof-full" style="display: none;">
                                    <?= nl2br(h($claim['proof_description'] ?? 'No proof provided')) ?>
                                </div>
                            </td>
                            <td><?= date('M d, Y', strtotime($claim['created_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="?action=approve&claim_id=<?= $claim['id'] ?>" 
                                   class="btn-approve" 
                                   onclick="return confirm('Approve this claim? The item will be marked as claimed.')">
                                    ✓ Approve
                                </a>
                                <a href="?action=reject&claim_id=<?= $claim['id'] ?>" 
                                   class="btn-reject" 
                                   onclick="return confirm('Reject this claim? This action cannot be undone.')">
                                    ✗ Reject
                                </a>
                                <a href="view-item.php?id=<?= $claim['item_id'] ?>" class="btn-view">
                                    View Item
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('items')">📦 Items Management</button>
            <button class="tab-btn" onclick="showTab('claims')">📋 Claims History</button>
            <button class="tab-btn" onclick="showTab('users')">👥 Users Management</button>
        </div>

        <!-- Items Tab - Updated status options to match your schema -->
        <div id="tab-items" class="tab-content active">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>All Items</h2>
                    <span class="section-badge">Total: <?= count($allItems) ?></span>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Reporter</th>
                                <th>Status</th>
                                <th>Claims</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allItems as $item): ?>
                            <tr>
                                <td>#<?= $item['id'] ?></td>
                                <td><strong><?= h($item['title']) ?></strong></td>
                                <td><?= h($item['category_name']) ?></td>
                                <td><?= h($item['location_name']) ?></td>
                                <td>
                                    <?php if ($item['reporter_first']): ?>
                                        <?= h($item['reporter_first'] . ' ' . $item['reporter_last']) ?>
                                    <?php else: ?>
                                        <em>Anonymous</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= getItemStatusBadge($item['status']) ?>">
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td><?= $item['claim_count'] ?></td>
                                <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <select name="new_status" onchange="this.form.submit()" style="padding: 4px; font-size: 12px;">
                                            <option value="">Change Status</option>
                                            <option value="unclaimed">Unclaimed</option>
                                            <option value="pending">Pending</option>
                                            <option value="claimed">Claimed</option>
                                        </select>
                                        <input type="hidden" name="update_item_status" value="1">
                                    </form>
                                    <a href="view-item.php?id=<?= $item['id'] ?>" class="btn-view">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Claims Tab - History Only (No Actions) -->
        <div id="tab-claims" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>
                        <span>📋</span>
                        Claims History
                    </h2>
                    <span class="section-badge">Total: <?= count($allClaims) ?></span>
                </div>
                
                <?php if (count($allClaims) > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Claimant</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Item Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allClaims as $claim): ?>
                                <tr>
                                    <td>#<?= $claim['id'] ?></td>
                                    <td>
                                        <strong><?= h($claim['item_title']) ?></strong>
                                        <br><small>Reporter: <?= h($claim['owner_first'] ?? 'Unknown') ?> <?= h($claim['owner_last'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?= h($claim['first_name'] ?? 'Unknown') ?> <?= h($claim['last_name'] ?? '') ?>
                                        <br><small><?= h($claim['email'] ?? 'No email') ?></small>
                                        <?php if (!empty($claim['student_id'])): ?>
                                            <br><small>ID: <?= h($claim['student_id']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($claim['claimer_phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="status-badge <?= getClaimStatusBadge($claim['status']) ?>">
                                            <?= ucfirst($claim['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($claim['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= getItemStatusBadge($claim['item_status']) ?>">
                                            <?= ucfirst($claim['item_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>No Claims Yet</h3>
                        <p>When users submit claim requests, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="tab-users" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>User Management</h2>
                    <span class="section-badge">Total: <?= count($allUsers) ?></span>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><strong><?= h($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
                                <td><?= h($user['student_id'] ?? 'N/A') ?></td>
                                <td><?= h($user['email']) ?></td>
                                <td>
                                    <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="action-buttons">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <a href="?toggle_user=1&user_id=<?= $user['id'] ?>" 
                                           class="<?= $user['is_active'] ? 'btn-deactivate' : 'btn-activate' ?>"
                                           onclick="return confirm('Toggle user status?')">
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div id="tab-categories" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Categories</h2>
                    <span class="section-badge">Total: <?= count($categories) ?></span>
                </div>
                
                <div class="category-list">
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <span><?= h($cat['name']) ?></span>
                        <span class="category-id">ID: <?= $cat['id'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Locations</h2>
                    <span class="section-badge">Total: <?= count($locations) ?></span>
                </div>
                
                <div class="category-list">
                    <?php foreach ($locations as $loc): ?>
                    <div class="category-item">
                        <span><?= h($loc['name']) ?></span>
                        <span class="category-id">ID: <?= $loc['id'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript for Tabs and Proof View -->
<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

function showProof(claimId) {
    const proofDiv = document.getElementById('proof-' + claimId);
    if (proofDiv.style.display === 'none') {
        proofDiv.style.display = 'block';
    } else {
        proofDiv.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>