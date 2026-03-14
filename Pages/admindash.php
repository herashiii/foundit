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

function getItemStatusBadge($status) {
    $badges = [
        'unclaimed' => 'badge-recent',
        'pending' => 'badge-pending',
        'claimed' => 'badge-claimed'
    ];
    return $badges[$status] ?? 'badge-default';
}

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
                
                if ($action === 'approve') {
                    $itemStmt = $pdo->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
                    $itemStmt->execute([$claim['item_id']]);
                }

                else if ($action === 'reject') {
                    $itemStmt = $pdo->prepare("UPDATE items SET status = 'unclaimed' WHERE id = ?");
                    $itemStmt->execute([$claim['item_id']]);
                }
                
                $pdo->commit();
                $actionMessage = "Claim #$claimId has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
                
                header('Location: admindash.php?message=' . urlencode($actionMessage));
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $actionError = "Error processing claim: " . $e->getMessage();
        }
    }
}

if (isset($_POST['mark_message_read'])) {
    $messageId = (int)$_POST['mark_message_read'];
    if ($messageId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ? AND status = 'unread'");
            $stmt->execute([$messageId]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
    exit;
}

if (isset($_GET['message'])) {
    $actionMessage = $_GET['message'];
}

// Update Item Status
if (isset($_POST['update_item_status']) && isset($_POST['item_id']) && isset($_POST['new_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];
    
    $allowedStatuses = ['unclaimed', 'pending', 'claimed'];
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $itemId]);
            $actionMessage = "Item #$itemId status updated to '$newStatus'.";
            
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
        
        header('Location: admindash.php?message=' . urlencode($actionMessage));
        exit;
    } catch (Exception $e) {
        $actionError = "Error updating user: " . $e->getMessage();
    }
}

$stats = [];
$stats['total_items'] = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$stats['recent_items'] = $pdo->query("SELECT COUNT(*) FROM items WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
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

// Get contact messages
$contactMessages = $pdo->query("
    SELECT * FROM contact_messages 
    ORDER BY 
        CASE status 
            WHEN 'unread' THEN 1 
            WHEN 'read' THEN 2 
            WHEN 'replied' THEN 3 
        END,
        created_at DESC
")->fetchAll();

if (isset($_GET['delete_message'])) {
    $messageId = (int)$_GET['delete_message'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        
        $actionMessage = "Message #$messageId has been deleted successfully.";
        header('Location: admindash.php?message=' . urlencode($actionMessage) . '&tab=messages');
        exit;
    } catch (Exception $e) {
        $actionError = "Error deleting message: " . $e->getMessage();
    }
}

$unreadMessages = array_filter($contactMessages, function($m) { 
    return $m['status'] === 'unread'; 
});
$unreadCount = count($unreadMessages);

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="css/admindash.css">

<main class="admin-dashboard">
    <div class="container">
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
                <span class="icon"><i class="fas fa-check-circle"></i></span>
                <span><?= h($actionMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($actionError): ?>
            <div class="alert alert-error">
                <span class="icon"><i class="fas fa-exclamation-triangle"></i></span>
                <span><?= h($actionError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Stats Grid  -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $stats['total_items'] ?></span>
                    <span class="stat-label">Total Items</span>
                </div>
                <div class="stat-breakdown">
                    <span class="stat-mini">Recent: <?= $stats['recent_items'] ?></span>
                    <span class="stat-mini">Unclaimed: <?= $stats['unclaimed_items'] ?></span>
                    <span class="stat-mini">Pending: <?= $stats['pending_items'] ?></span>
                    <span class="stat-mini">Claimed: <?= $stats['claimed_items'] ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
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
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
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

        <!-- Pending Claims Section -->
        <?php if (count($pendingClaims) > 0): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i> Pending Claims - Action Required</h2>
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
                                <br><small>Reporter: <?= h($claim['owner_first'] ?? 'Unknown') ?></small>
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
            <button class="tab-btn active" onclick="showTab('items')">
                <i class="fas fa-boxes"></i> Items Management
            </button>
            <button class="tab-btn" onclick="showTab('claims')">
                <i class="fas fa-file-signature"></i> Claims History
            </button>
            <button class="tab-btn" onclick="showTab('users')">
                <i class="fas fa-user-cog"></i> Users Management
            </button>
            <button class="tab-btn" onclick="showTab('messages')" id="messages-tab-btn">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($unreadCount > 0): ?>
                    <span class="tab-unread-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Items Tab  -->
        <div id="tab-items" class="tab-content">
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

        <!-- Claims Tab -->
        <div id="tab-claims" class="tab-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-signature"></i> Claims History</h2>
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
                        <div class="empty-icon"><i class="fas fa-inbox fa-4x"></i></div>
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

        <!-- Messages Tab -->
        <div id="tab-messages" class="tab-content">
            <div class="dashboard-section">
                <div class="messages-header">
                    <div class="header-title">
                        <h2>
                            <i class="fas fa-inbox"></i>
                            Messages
                        </h2>
                        <?php if ($unreadCount > 0): ?>
                            <div class="unread-count-badge">
                                <span class="unread-count"><?= $unreadCount ?></span> unread
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="header-actions">
                        <button class="btn-refresh" onclick="refreshWithTab()" title="Refresh messages">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Message Filters -->
                <div class="message-filters">
                    <button class="filter-btn active" onclick="filterMessages('all', this)">
                        <i class="fas fa-envelope"></i> All
                        <span class="filter-count"><?= count($contactMessages) ?></span>
                    </button>
                    <button class="filter-btn" onclick="filterMessages('unread', this)">
                        <i class="fas fa-circle" style="color: #9B2C2C;"></i> Unread
                        <?php if ($unreadCount > 0): ?>
                            <span class="filter-count unread"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="filter-btn" onclick="filterMessages('replied', this)">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i> Replied
                    </button>
                </div>
                
                <?php if (count($contactMessages) > 0): ?>
                    <div class="messages-list">
                        <?php foreach ($contactMessages as $msg): 
                            $isUnread = $msg['status'] === 'unread';
                        ?>
                            <div class="message-item <?= $isUnread ? 'message-unread' : '' ?>" 
                                data-status="<?= $msg['status'] ?>"
                                data-message-id="<?= $msg['id'] ?>">
                                
                                <?php if ($isUnread): ?>
                                    <div class="unread-indicator" title="Unread message"></div>
                                <?php endif; ?>

                                <!-- Message Header -->
                                <div class="message-header-compact" onclick="toggleMessageExpand(<?= $msg['id'] ?>)">
                                    <div class="message-sender-info">
                                        <div class="sender-avatar <?= $isUnread ? 'avatar-unread' : '' ?>">
                                            <?= strtoupper(substr($msg['name'], 0, 1)) ?>
                                        </div>
                                        <div class="message-details">
                                            <div class="message-title-row">
                                                <span class="sender-name">
                                                    <strong><?= h($msg['name']) ?></strong>
                                                    <?php if ($isUnread): ?>
                                                        <span class="new-badge">NEW</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="message-time">
                                                    <i class="far fa-clock"></i>
                                                    <?= date('M d, Y', strtotime($msg['created_at'])) ?>
                                                </span>
                                            </div>
                                            <div class="message-subject-row">
                                                <span class="sender-email">
                                                    <i class="fas fa-envelope"></i> <?= h($msg['email']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Message Preview -->
                                <div class="message-preview" onclick="toggleMessageExpand(<?= $msg['id'] ?>)">
                                    <p><?= h(substr($msg['message'], 0, 120)) ?><?= strlen($msg['message']) > 120 ? '...' : '' ?></p>
                                </div>
                                
                                <!-- Expandable Full Message -->
                                <div id="message-full-<?= $msg['id'] ?>" class="message-full" style="display: none;">
                                    <div class="full-message-container">
                                        <div class="full-message-header">
                                            <h4><i class="fas fa-quote-left"></i> Full Message</h4>
                                            <button class="btn-expand" onclick="toggleMessageExpand(<?= $msg['id'] ?>)">
                                                <i class="fas fa-chevron-up"></i> Collapse
                                            </button>
                                        </div>
                                        <div class="full-message-content">
                                            <p><?= nl2br(h($msg['message'])) ?></p>
                                        </div>
                                        
                                        <?php if (!empty($msg['admin_reply'])): ?>
                                            <div class="reply-history">
                                                <h4><i class="fas fa-reply"></i> Your Reply</h4>
                                                <p><?= nl2br(h($msg['admin_reply'])) ?></p>
                                                <small class="reply-date">
                                                    <i class="far fa-calendar-alt"></i> 
                                                    Replied on <?= date('M d, Y', strtotime($msg['updated_at'])) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Message Actions -->
                                <div class="message-actions">
                                    <div class="action-left">
                                        <span class="message-status-indicator status-<?= $msg['status'] ?>">
                                            <?php if ($msg['status'] === 'unread'): ?>
                                                <i class="fas fa-circle"></i> Unread
                                            <?php elseif ($msg['status'] === 'replied'): ?>
                                                <i class="fas fa-check-circle"></i> Replied
                                            <?php else: ?>
                                                <i class="fas fa-envelope-open"></i> Read
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="action-right">
                                        <button onclick="openReplyModal(<?= $msg['id'] ?>, '<?= h(addslashes($msg['name'])) ?>', '<?= h($msg['email']) ?>')" 
                                                class="action-icon-btn reply-btn">
                                            <i class="fas fa-reply"></i>
                                            <span>Reply</span>
                                        </button>
                                        
                                        <a href="mailto:<?= h($msg['email']) ?>" 
                                        class="action-icon-btn email-btn">
                                            <i class="fas fa-envelope"></i>
                                            <span>Email</span>
                                        </a>
                                        
                                        <button onclick="deleteMessage(<?= $msg['id'] ?>)" 
                                                class="action-icon-btn delete-btn">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox fa-4x"></i>
                        </div>
                        <h3>No Messages Yet</h3>
                        <p>When users send messages through the contact form, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-reply" style="color: #9B2C2C;"></i> Reply to Message</h2>
                <button class="modal-close" onclick="closeReplyModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="replyForm" method="POST" action="reply-message.php">
                <input type="hidden" name="message_id" id="replyMessageId">
                
                <div class="modal-body">
                    <!-- Recipient Card -->
                    <div class="recipient-card">
                        <div class="recipient-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="recipient-details">
                            <div class="recipient-name" id="replyToName"></div>
                            <div class="recipient-email" id="replyToEmail"></div>
                        </div>
                    </div>

                    <!-- Subject Line -->
                    <div class="form-group">
                        <label for="reply_subject">
                            <i class="fas fa-tag"></i> Subject
                        </label>
                        <div class="subject-display">
                            Re: Message from FoundiT Contact Form
                        </div>
                        <input type="hidden" name="subject" value="Re: Message from FoundiT Contact Form">
                    </div>

                    <!-- Reply Message -->
                    <div class="form-group">
                        <label for="reply_message">
                            <i class="fas fa-pencil-alt"></i> Your Reply <span class="required-star">*</span>
                        </label>
                        <textarea name="reply_message" id="reply_message" rows="6" required 
                                placeholder="Type your reply here..." autofocus></textarea>
                        <small class="field-hint">
                            <i class="fas fa-info-circle"></i> 
                            Your reply will be saved and the message will be marked as replied.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeReplyModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- JavaScript for Tabs and Proof View -->
<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('tab-' + tabName).classList.add('active');
    
    event.target.classList.add('active');
    
    sessionStorage.setItem('activeTab', tabName);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    
    let activeTab = urlTab || sessionStorage.getItem('activeTab') || 'items';
    
    const tabBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => 
        btn.getAttribute('onclick')?.includes(activeTab)
    );
    
    if (tabBtn) {
        const tabName = activeTab;
        
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById('tab-' + tabName).classList.add('active');
        
        tabBtn.classList.add('active');
    }
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.style) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert && alert.style) {
                        alert.style.display = 'none';
                    }
                }, 500);
            }
        }, 5000);
    });
    
    alerts.forEach(alert => {
        alert.addEventListener('click', function() {
            this.style.transition = 'opacity 0.3s ease';
            this.style.opacity = '0';
            setTimeout(() => {
                this.style.display = 'none';
            }, 300);
        });
    });
});

function showProof(claimId) {
    const proofDiv = document.getElementById('proof-' + claimId);
    if (proofDiv.style.display === 'none') {
        proofDiv.style.display = 'block';
    } else {
        proofDiv.style.display = 'none';
    }
}

// Show full message
function showMessage(messageId) {
    const messageDiv = document.getElementById('message-' + messageId);
    if (messageDiv.style.display === 'none') {
        messageDiv.style.display = 'block';
    } else {
        messageDiv.style.display = 'none';
    }
}

// Show reply
function showReply(messageId) {
    const replyDiv = document.getElementById('reply-' + messageId);
    if (replyDiv.style.display === 'none') {
        replyDiv.style.display = 'block';
    } else {
        replyDiv.style.display = 'none';
    }
}

// Open reply modal
function openReplyModal(messageId, name, email) {
    document.getElementById('replyMessageId').value = messageId;
    document.getElementById('replyToName').textContent = name;
    document.getElementById('replyToEmail').textContent = email;
    document.getElementById('replyModal').style.display = 'flex';
    
    // Mark as read when replying
    const messageItem = document.querySelector(`.message-item[data-message-id="${messageId}"]`);
    if (messageItem && messageItem.classList.contains('message-unread')) {
        messageItem.classList.remove('message-unread');
        messageItem.dataset.status = 'read'; // Update data-status for filtering
        const unreadIndicator = messageItem.querySelector('.unread-indicator');
        if (unreadIndicator) unreadIndicator.remove();
        const newBadge = messageItem.querySelector('.new-badge');
        if (newBadge) newBadge.remove();
        
        // Update status indicator text
        const statusIndicator = messageItem.querySelector('.message-status-indicator');
        if (statusIndicator) {
            statusIndicator.className = 'message-status-indicator status-read';
            statusIndicator.innerHTML = '<i class="fas fa-envelope-open"></i> Read';
        }
        
        fetch('admindash.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'mark_message_read=' + messageId
        }).then(() => {
            updateUnreadCount();
            
            const activeFilter = document.querySelector('.filter-btn.active');
            if (activeFilter && activeFilter.textContent.includes('Unread')) {
                messageItem.style.display = 'none';
            }
        });
    }
}

// Close reply modal
function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
    document.getElementById('reply_message').value = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('replyModal');
    if (event.target === modal) {
        closeReplyModal();
    }
}

// Filter messages by status
function filterMessages(status, btn) {
    const messages = document.querySelectorAll('.message-item');
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    // Update active button
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    // Filter messages
    messages.forEach(msg => {
        if (status === 'all') {
            msg.style.display = 'block';
        } else {
            if (msg.dataset.status === status) {
                msg.style.display = 'block';
            } else {
                msg.style.display = 'none';
            }
        }
    });
}

// Toggle message expansion
function toggleMessageExpand(messageId) {
    const fullMessage = document.getElementById('message-full-' + messageId);
    const messageItem = document.querySelector(`.message-item[data-message-id="${messageId}"]`);
    
    if (fullMessage.style.display === 'none') {
        fullMessage.style.display = 'block';
        
        // If message is unread, mark it as read when expanded
        if (messageItem && messageItem.classList.contains('message-unread')) {
            messageItem.classList.remove('message-unread');
            messageItem.dataset.status = 'read';
            const unreadIndicator = messageItem.querySelector('.unread-indicator');
            if (unreadIndicator) unreadIndicator.remove();
            const newBadge = messageItem.querySelector('.new-badge');
            if (newBadge) newBadge.remove();
            
            const statusIndicator = messageItem.querySelector('.message-status-indicator');
            if (statusIndicator) {
                statusIndicator.className = 'message-status-indicator status-read';
                statusIndicator.innerHTML = '<i class="fas fa-envelope-open"></i> Read';
            }
            
            fetch('admindash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_message_read=' + messageId
            }).then(() => {
                updateUnreadCount();
            });
        }
    } else {
        fullMessage.style.display = 'none';
    }
}

function updateUnreadCount() {
    const unreadMessages = document.querySelectorAll('.message-item.message-unread').length;
    
    const tabBadge = document.querySelector('#messages-tab-btn .tab-unread-badge');
    if (tabBadge) {
        if (unreadMessages > 0) {
            tabBadge.textContent = unreadMessages;
            tabBadge.style.display = 'inline-block';
        } else {
            tabBadge.style.display = 'none';
        }
    }
    
    const headerBadge = document.querySelector('.unread-count-badge .unread-count');
    if (headerBadge) {
        if (unreadMessages > 0) {
            headerBadge.textContent = unreadMessages;
            document.querySelector('.unread-count-badge').style.display = 'inline-flex';
        } else {
            document.querySelector('.unread-count-badge').style.display = 'none';
        }
    }
    
    const filterUnread = document.querySelector('.filter-btn[onclick*="unread"] .filter-count.unread');
    if (filterUnread) {
        if (unreadMessages > 0) {
            filterUnread.textContent = unreadMessages;
            filterUnread.style.display = 'inline-block';
        } else {
            filterUnread.style.display = 'none';
        }
    }
}

function refreshWithTab() {
    const currentTab = sessionStorage.getItem('activeTab') || 'items';
    window.location.href = 'admindash.php?tab=' + currentTab;
}

function deleteMessage(messageId) {
    if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
        window.location.href = 'admindash.php?delete_message=' + messageId + '&tab=messages';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>