<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Please login to access your dashboard'));
    exit;
}

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$pdo = db();
$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get items this user has reported
$stmt = $pdo->prepare("
    SELECT i.*, c.name as category_name, l.name as location_name,
           (SELECT COUNT(*) FROM item_photos WHERE item_id = i.id) as photo_count
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.found_location_id = l.id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$user_id]);
$my_items = $stmt->fetchAll();

// Get claim requests for user's items
$stmt = $pdo->prepare("
    SELECT cr.*, i.title as item_title
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    WHERE i.user_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$user_id]);
$my_claims = $stmt->fetchAll();

// Calculate stats
$total_items = count($my_items);
$total_claims = count($my_claims);
$pending_claims = array_filter($my_claims, function($claim) {
    return $claim['status'] === 'pending';
});
?>

<main class="dashboard-page">
    <div class="container">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-text">
                <h1>Welcome back, <?= h($user['first_name']) ?>! <i class="fas fa-hand-wave" style="color: #9B2C2C;"></i></h1>
                <p>Here's what's happening with your lost & found items</p>
            </div>
            <div class="header-actions">
                <a href="turn-in-item.php" class="btn btn-primary btn-large">
                    <i class="fas fa-plus-circle btn-icon"></i>
                    Report Found Item
                </a>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $total_items ?></span>
                    <span class="stat-label">Items Reported</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $total_claims ?></span>
                    <span class="stat-label">Claims Received</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($pending_claims) ?></span>
                    <span class="stat-label">Pending Claims</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= count(array_filter($my_items, function($item) { return $item['status'] === 'returned'; })) ?></span>
                    <span class="stat-label">Resolved Items</span>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Profile Card -->
            <div class="dashboard-card profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div class="profile-title">
                        <h2><?= h($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                        <span class="profile-role"><i class="fas fa-graduation-cap" style="margin-right: 4px;"></i> Student</span>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-id-card" style="margin-right: 6px;"></i> Student ID</span>
                        <span class="detail-value"><?= h($user['student_id'] ?? 'Not set') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-envelope" style="margin-right: 6px;"></i> Email</span>
                        <span class="detail-value"><?= h($user['email']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-phone" style="margin-right: 6px;"></i> Phone</span>
                        <span class="detail-value"><?= h($user['phone'] ?? 'Not provided') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-calendar-alt" style="margin-right: 6px;"></i> Member Since</span>
                        <span class="detail-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="dashboard-card activity-card">
                <div class="card-header">
                    <h2>
                        <span class="card-icon"><i class="fas fa-clock"></i></span>
                        Recent Activity
                    </h2>
                </div>
                
                <div class="activity-list">
                    <?php 
                    $recent_activities = array_merge(
                        array_map(function($item) { 
                            return ['type' => 'item', 'data' => $item, 'date' => $item['created_at']]; 
                        }, array_slice($my_items, 0, 3)),
                        array_map(function($claim) { 
                            return ['type' => 'claim', 'data' => $claim, 'date' => $claim['created_at']]; 
                        }, array_slice($my_claims, 0, 3))
                    );
                    
                    usort($recent_activities, function($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });
                    
                    $recent_activities = array_slice($recent_activities, 0, 5);
                    
                    if (empty($recent_activities)): ?>
                        <div class="empty-activity">
                            <i class="fas fa-inbox" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?= $activity['type'] === 'item' ? '<i class="fas fa-box"></i>' : '<i class="fas fa-file-signature"></i>' ?>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title">
                                        <?php if ($activity['type'] === 'item'): ?>
                                            You reported "<?= h($activity['data']['title']) ?>"
                                        <?php else: ?>
                                            New claim received for "<?= h($activity['data']['item_title']) ?>"
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-meta">
                                        <i class="far fa-calendar-alt" style="margin-right: 4px;"></i>
                                        <?= date('M d, Y g:i A', strtotime($activity['date'])) ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= h($activity['type'] === 'item' ? $activity['data']['status'] : $activity['data']['status']) ?>">
                                    <?= ucfirst(h($activity['type'] === 'item' ? $activity['data']['status'] : $activity['data']['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Items Section -->
            <div class="dashboard-card items-card full-width">
                <div class="card-header">
                    <h2>
                        <span class="card-icon"><i class="fas fa-boxes"></i></span>
                        Items I've Reported
                        <span class="item-count">(<?= $total_items ?>)</span>
                    </h2>
                    <div class="card-filters">
                        <select class="filter-select" onchange="filterItems(this.value)">
                            <option value="all">All Items</option>
                            <option value="recent">Recent</option>
                            <option value="pending">Pending</option>
                            <option value="claimed">Claimed</option>
                            <option value="returned">Returned</option>
                        </select>
                    </div>
                </div>
                
                <?php if (count($my_items) > 0): ?>
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Found At</th>
                                    <th>Date Found</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_items as $item): ?>
                                    <tr class="item-row" data-status="<?= $item['status'] ?>">
                                        <td>
                                            <strong><?= h($item['title']) ?></strong>
                                        </td>
                                        <td><?= h($item['category_name']) ?></td>
                                        <td><?= h($item['location_name']) ?></td>
                                        <td><i class="far fa-calendar-alt" style="margin-right: 4px; color: #64748b;"></i><?= date('M d, Y', strtotime($item['found_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= h($item['status']) ?>">
                                                <?= ucfirst(h($item['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="view-item.php?id=<?= $item['id'] ?>" class="btn-action view-btn" title="View item details">
                                                <i class="fas fa-eye"></i> <span class="action-text">View</span>
                                            </a>
                                            <?php if ($item['status'] === 'recent'): ?>
                                                <a href="edit-item.php?id=<?= $item['id'] ?>" class="btn-action edit-btn" title="Edit item">
                                                    <i class="fas fa-edit"></i> <span class="action-text">Edit</span>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <h3>No items reported yet</h3>
                        <p>When you find and report lost items, they'll appear here.</p>
                        <a href="turn-in-item.php" class="btn btn-primary">Report Your First Item</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Claims Received Section -->
            <div class="dashboard-card claims-card full-width">
                <div class="card-header">
                    <h2>
                        <span class="card-icon"><i class="fas fa-file-signature"></i></span>
                        Claims on My Items
                        <span class="item-count">(<?= $total_claims ?>)</span>
                    </h2>
                </div>
                
                <?php if (count($my_claims) > 0): ?>
                    <div class="claims-grid">
                        <?php foreach ($my_claims as $claim): ?>
                            <div class="claim-card">
                                <div class="claim-header">
                                    <h3><?= h($claim['item_title']) ?></h3>
                                    <span class="status-badge status-<?= h($claim['status']) ?>">
                                        <?= ucfirst(h($claim['status'])) ?>
                                    </span>
                                </div>
                                
                                <div class="claim-details">
                                    <div class="claimant-info">
                                        <i class="fas fa-user" style="margin-right: 6px; color: #64748b;"></i>
                                        <strong>Claimant:</strong> <?= h($claim['claimer_name'] ?? 'Anonymous') ?>
                                    </div>
                                    <div class="claim-date">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #64748b;"></i>
                                        <strong>Date:</strong> <?= date('M d, Y', strtotime($claim['created_at'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($claim['proof_description'])): ?>
                                        <div class="claim-proof">
                                            <strong><i class="fas fa-file-alt" style="margin-right: 4px;"></i> Proof provided:</strong>
                                            <p><?= h(substr($claim['proof_description'], 0, 150)) ?>...</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="claim-footer">
                                    <a href="view-item.php?id=<?= $claim['item_id'] ?>" class="btn btn-secondary btn-small">
                                        <i class="fas fa-eye"></i> View Item
                                    </a>
                                    <?php if ($claim['status'] === 'pending'): ?>
                                        <a href="view-claim.php?id=<?= $claim['id'] ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-check-circle"></i> Review Claim
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state compact">
                        <i class="fas fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                        <p>No claims received yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        // Filter items by status
        function filterItems(status) {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = 'table-row';
                } else {
                    if (row.dataset.status === status) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
    </script>
</main>

<style>
/* ======================================= */
/* DASHBOARD PAGE - HCI FRIENDLY DESIGN   */
/* ======================================= */

.dashboard-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Welcome Header */
.welcome-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.welcome-text h1 {
    font-size: 2rem;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 700;
}

.welcome-text p {
    color: #64748b;
    font-size: 1rem;
}

.btn-large {
    padding: 14px 28px;
    font-size: 1rem;
}

.btn-icon {
    margin-right: 8px;
    font-size: 1.2rem;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e2e8f0;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    border-color: #9B2C2C;
}

.stat-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    border-radius: 12px;
    color: #9B2C2C;
}

.stat-content {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    color: #64748b;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.dashboard-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    transition: box-shadow 0.2s;
}

.dashboard-card:hover {
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.full-width {
    grid-column: span 2;
}

/* Card Header */
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.card-header h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1e293b;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
}

.card-icon {
    font-size: 1.4rem;
}

.item-count {
    color: #64748b;
    font-weight: 400;
    margin-left: 5px;
}

.view-all-link {
    color: #9B2C2C;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: #601515;
    text-decoration: underline;
}

/* Profile Card */
.profile-card {
    grid-column: 1;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.profile-avatar {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #9B2C2C 0%, #601515 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.5rem;
}

.profile-title h2 {
    margin: 0 0 4px;
    color: #1e293b;
    font-size: 1.3rem;
}

.profile-role {
    color: #64748b;
    font-size: 0.9rem;
}

.profile-details {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    width: 110px;
    color: #64748b;
    font-size: 0.9rem;
}

.detail-value {
    flex: 1;
    color: #1e293b;
    font-weight: 500;
}

/* Activity Card */
.activity-card {
    grid-column: 2;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 12px;
    transition: transform 0.2s;
}

.activity-item:hover {
    transform: translateX(5px);
    background: #f1f5f9;
}

.activity-icon {
    font-size: 1.5rem;
    width: 40px;
    text-align: center;
    color: #9B2C2C;
}

.activity-details {
    flex: 1;
}

.activity-title {
    color: #1e293b;
    font-weight: 500;
    margin-bottom: 4px;
}

.activity-meta {
    color: #64748b;
    font-size: 0.8rem;
}

.empty-activity {
    text-align: center;
    color: #94a3b8;
    padding: 30px;
}

/* Items Table */
.table-responsive {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    text-align: left;
    padding: 16px;
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
}

.items-table td {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
}

.items-table tr:hover {
    background: #f8fafc;
}

.actions-cell {
    display: flex;
    gap: 12px;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: #475569;
    background: #f1f5f9;
    transition: all 0.2s;
}

.btn-action:hover {
    background: #e2e8f0;
    color: #9B2C2C;
}

.action-text {
    font-size: 0.85rem;
}

/* Filter Select */
.filter-select {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    font-size: 0.9rem;
    color: #334155;
    background: white;
    cursor: pointer;
    outline: none;
}

.filter-select:hover {
    border-color: #9B2C2C;
}

.filter-select:focus {
    border-color: #9B2C2C;
    box-shadow: 0 0 0 3px rgba(155,44,44,0.1);
}

/* Status Badges - keeping your colors */
.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-recent {
    background: #e3f2fd;
    color: #1976d2;
    border: 1px solid #90caf9;
}

.status-pending {
    background: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffb74d;
}

.status-claimed {
    background: #e8f5e9;
    color: #388e3c;
    border: 1px solid #a5d6a7;
}

.status-returned {
    background: #f3e5f5;
    color: #8e24aa;
    border: 1px solid #ce93d8;
}

.status-approved {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.status-rejected {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

/* Claims Grid */
.claims-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.claim-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.claim-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    border-color: #9B2C2C;
}

.claim-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.claim-header h3 {
    color: #1e293b;
    font-size: 1rem;
    margin: 0;
}

.claim-details {
    margin-bottom: 16px;
}

.claimant-info, .claim-date {
    color: #475569;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.claim-proof {
    margin-top: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    font-size: 0.9rem;
}

.claim-proof p {
    margin-top: 6px;
    color: #475569;
    line-height: 1.5;
}

.claim-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 16px;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    color: #cbd5e1;
}

.empty-state h3 {
    color: #1e293b;
    margin-bottom: 8px;
}

.empty-state p {
    color: #64748b;
    margin-bottom: 20px;
}

.empty-state.compact {
    padding: 30px;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    gap: 8px;
}

.btn-primary {
    background: #9B2C2C;
    color: white;
}

.btn-primary:hover {
    background: #601515;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(155,44,44,0.3);
}

.btn-secondary {
    background: white;
    color: #334155;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f8fafc;
    border-color: #9B2C2C;
    color: #9B2C2C;
}

.btn-small {
    padding: 6px 16px;
    font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-card,
    .activity-card,
    .full-width {
        grid-column: 1;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .welcome-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .claims-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .detail-item {
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-label {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .dashboard-card {
        padding: 20px;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .items-table td {
        padding: 12px;
    }
    
    .actions-cell {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}

/* Loading State */
.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Focus Visible for Accessibility */
*:focus-visible {
    outline: 3px solid #9B2C2C;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .btn,
    .btn-logout,
    .filter-select {
        display: none !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>