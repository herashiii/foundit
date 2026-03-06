
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

// Helper function for status badges
function getStatusBadge($status) {
    $badges = [
        'recent' => 'badge-recent',
        'pending' => 'badge-pending',
        'claimed' => 'badge-claimed',
        'returned' => 'badge-returned',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected'
    ];
    return $badges[$status] ?? 'badge-default';
}

$pdo = db();
$adminId = $_SESSION['user_id'];

// Get admin info - QUERY FOR EXISTING ADMIN
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    // If somehow not an admin, redirect
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
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $actionError = "Error processing claim: " . $e->getMessage();
        }
    }
}

// Update Item Status
if (isset($_POST['update_item_status']) && isset($_POST['item_id']) && isset($_POST['new_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];
    
    $allowedStatuses = ['recent', 'pending', 'claimed', 'returned'];
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $itemId]);
            $actionMessage = "Item #$itemId status updated to '$newStatus'.";
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
    } catch (Exception $e) {
        $actionError = "Error updating user: " . $e->getMessage();
    }
}

// Get statistics
$stats = [];

// Total items
$stats['total_items'] = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();

// Items by status
$stats['recent_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'recent'")->fetchColumn();
$stats['pending_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn();
$stats['claimed_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'claimed'")->fetchColumn();
$stats['returned_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'returned'")->fetchColumn();

// Total users
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['admin_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$stats['student_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

// Total claims
$stats['total_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests")->fetchColumn();
$stats['pending_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'pending'")->fetchColumn();
$stats['approved_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'approved'")->fetchColumn();
$stats['rejected_claims'] = $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'rejected'")->fetchColumn();

// Get pending claims (priority)
$pendingClaims = $pdo->query("
    SELECT cr.*, i.title as item_title, i.status as item_status, 
           u.first_name, u.last_name, u.email, u.student_id
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    LEFT JOIN users u ON cr.user_id = u.id
    WHERE cr.status = 'pending'
    ORDER BY cr.created_at ASC
")->fetchAll();

// Get recent claims (all)
$recentClaims = $pdo->query("
    SELECT cr.*, i.title as item_title, i.status as item_status,
           u.first_name, u.last_name, u.email, u.student_id
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    LEFT JOIN users u ON cr.user_id = u.id
    ORDER BY cr.created_at DESC
    LIMIT 20
")->fetchAll();

// Get recent items with details
$recentItems = $pdo->query("
    SELECT i.*, c.name as category_name, l.name as location_name,
           u.first_name as reporter_first, u.last_name as reporter_last,
           (SELECT COUNT(*) FROM claim_requests WHERE item_id = i.id) as claim_count
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.found_location_id = l.id
    LEFT JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC
    LIMIT 20
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

// Get all categories for management
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-dashboard">
    <div class="container">
        <!-- Admin Header -->
        <div class="admin-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <strong><?= h($admin['first_name'] . ' ' . $admin['last_name']) ?></strong> (Administrator)</p>
                <p class="admin-badge" style="color: var(--mono-red-3); font-size: 0.9rem;">
                    Last login: <?= date('M d, Y h:i A', strtotime($admin['created_at'] ?? 'now')) ?>
                </p>
            </div>
            <div class="admin-actions">
                <a href="../Pages/index.php" class="btn btn-secondary">View Site</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>

        <!-- Action Messages -->
        <?php if ($actionMessage): ?>
            <div class="alert alert-success" role="alert" style="margin-bottom: 24px;">
                <span class="icon" aria-hidden="true">✅</span>
                <span><?= h($actionMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($actionError): ?>
            <div class="alert alert-error" role="alert" style="margin-bottom: 24px;">
                <span class="icon" aria-hidden="true">⚠️</span>
                <span><?= h($actionError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_items'] ?></span>
                    <span class="stat-label">Total Items</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini" style="color: #3498db;">Recent: <?= $stats['recent_items'] ?></span>
                    <span class="stat-mini" style="color: #f39c12;">Pending: <?= $stats['pending_items'] ?></span>
                    <span class="stat-mini" style="color: #27ae60;">Claimed: <?= $stats['claimed_items'] ?></span>
                    <span class="stat-mini" style="color: #95a5a6;">Returned: <?= $stats['returned_items'] ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_users'] ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini" style="color: #e74c3c;">Admins: <?= $stats['admin_users'] ?></span>
                    <span class="stat-mini" style="color: #3498db;">Students: <?= $stats['student_users'] ?></span>
                    <span class="stat-mini" style="color: #27ae60;">Active: <?= $stats['active_users'] ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_claims'] ?></span>
                    <span class="stat-label">Total Claims</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini" style="color: #f39c12;">Pending: <?= $stats['pending_claims'] ?></span>
                    <span class="stat-mini" style="color: #27ae60;">Approved: <?= $stats['approved_claims'] ?></span>
                    <span class="stat-mini" style="color: #e74c3c;">Rejected: <?= $stats['rejected_claims'] ?></span>
                </div>
            </div>
        </div>

        <!-- Pending Claims Section (Priority) -->
        <?php if (count($pendingClaims) > 0): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>⚠️ Pending Claims - Action Required</h2>
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
                            </td>
                            <td>
                                <?= h($claim['first_name'] . ' ' . $claim['last_name']) ?><br>
                                <small><?= h($claim['student_id'] ?? $claim['email']) ?></small>
                            </td>
                            <td><?= h($claim['claimer_phone'] ?? 'N/A') ?></td>
                            <td>
                                <div class="proof-preview">
                                    <?= h(substr($claim['proof_description'], 0, 50)) ?>...
                                    <button class="btn-view-proof" onclick="showProof(<?= $claim['id'] ?>)">View</button>
                                </div>
                                <div id="proof-<?= $claim['id'] ?>" class="proof-full" style="display: none;">
                                    <?= nl2br(h($claim['proof_description'])) ?>
                                </div>
                            </td>
                            <td><?= date('M d, Y', strtotime($claim['created_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="?action=approve&claim_id=<?= $claim['id'] ?>" 
                                   class="btn-approve" 
                                   onclick="return confirm('Approve this claim? The item will be marked as claimed.')">✓ Approve</a>
                                <a href="?action=reject&claim_id=<?= $claim['id'] ?>" 
                                   class="btn-reject" 
                                   onclick="return confirm('Reject this claim? This action cannot be undone.')">✗ Reject</a>
                                <a href="view-item.php?id=<?= $claim['item_id'] ?>" class="btn-view">View Item</a>
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
            <button class="tab-btn" onclick="showTab('categories')">🏷️ Categories & Locations</button>
        </div>

        <!-- Items Tab -->
        <div id="tab-items" class="tab-content active">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Items</h2>
                    <a href="manage-items.php" class="btn-view-all">View All →</a>
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
                            <?php foreach ($recentItems as $item): ?>
                            <tr>
                                <td>#<?= $item['id'] ?></td>
                                <td>
                                    <strong><?= h($item['title']) ?></strong>
                                </td>
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
                                    <span class="status-badge <?= getStatusBadge($item['status']) ?>">
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
                                            <option value="recent">Recent</option>
                                            <option value="pending">Pending</option>
                                            <option value="claimed">Claimed</option>
                                            <option value="returned">Returned</option>
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

        <!-- Claims Tab -->
        <div id="tab-claims" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>All Claims</h2>
                </div>
                
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentClaims as $claim): ?>
                            <tr>
                                <td>#<?= $claim['id'] ?></td>
                                <td>
                                    <strong><?= h($claim['item_title']) ?></strong>
                                    <br><small>Status: <?= $claim['item_status'] ?></small>
                                </td>
                                <td>
                                    <?= h($claim['first_name'] . ' ' . $claim['last_name']) ?><br>
                                    <small><?= h($claim['email']) ?></small>
                                </td>
                                <td><?= h($claim['claimer_phone'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="status-badge <?= getStatusBadge($claim['status']) ?>">
                                        <?= ucfirst($claim['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($claim['created_at'])) ?></td>
                                <td class="action-buttons">
                                    <?php if ($claim['status'] === 'pending'): ?>
                                        <a href="?action=approve&claim_id=<?= $claim['id'] ?>" class="btn-approve-small">✓</a>
                                        <a href="?action=reject&claim_id=<?= $claim['id'] ?>" class="btn-reject-small">✗</a>
                                    <?php endif; ?>
                                    <a href="view-item.php?id=<?= $claim['item_id'] ?>" class="btn-view">Item</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="tab-users" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>User Management</h2>
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
                                <td>
                                    <strong><?= h($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                </td>
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
                                           class="btn-<?= $user['is_active'] ? 'deactivate' : 'activate' ?>"
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