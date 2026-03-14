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

// Get claim requests the logged-in user has submitted
$stmt = $pdo->prepare("
    SELECT cr.*, i.title as item_title
    FROM claim_requests cr
    JOIN items i ON cr.item_id = i.id
    WHERE cr.user_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$user_id]);
$my_requests = $stmt->fetchAll();

// Calculate stats
$total_items = count($my_items);
$total_claims = count($my_claims);
$total_requests = count($my_requests);
$pending_claims = array_filter($my_claims, function($claim) {
    return $claim['status'] === 'pending';
});
?>

<link rel="stylesheet" href="css/dashboard.css">


<main class="dashboard-page" role="main">
    <div class="container">
        <div class="welcome-header">
            <div class="welcome-text">
                <h1>Welcome back, <?= h($user['first_name']) ?>! <i class="fas fa-hand-wave" style="color: #9B2C2C;" aria-hidden="true"></i></h1>
                <p>Here's what's happening with your lost & found items</p>
            </div>
            <div class="header-actions">
                <a href="turn-in-item.php" class="btn btn-primary btn-large">
                    <i class="fas fa-plus-circle btn-icon" aria-hidden="true"></i>
                    Report Found Item
                </a>
            </div>
        </div>

        <div class="stats-row" aria-label="Dashboard Statistics">
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true"><i class="fas fa-box"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $total_items ?></span>
                    <span class="stat-label">Items Reported</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $total_claims ?></span>
                    <span class="stat-label">Claims Received</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($pending_claims) ?></span>
                    <span class="stat-label">Pending Claims</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= count(array_filter($my_items, function($item) { return $item['status'] === 'returned'; })) ?></span>
                    <span class="stat-label">Resolved Items</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="dashboard-card profile-card">
                <div class="profile-header">
                    <div class="profile-avatar" aria-hidden="true">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div class="profile-title">
                        <h2><?= h($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                        <span class="profile-role"><i class="fas fa-graduation-cap" style="margin-right: 4px;" aria-hidden="true"></i> Student</span>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-id-card" style="margin-right: 6px;" aria-hidden="true"></i> Student ID</span>
                        <span class="detail-value"><?= h($user['student_id'] ?? 'Not set') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-envelope" style="margin-right: 6px;" aria-hidden="true"></i> Email</span>
                        <span class="detail-value"><?= h($user['email']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-phone" style="margin-right: 6px;" aria-hidden="true"></i> Phone</span>
                        <span class="detail-value"><?= h($user['phone'] ?? 'Not provided') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-calendar-alt" style="margin-right: 6px;" aria-hidden="true"></i> Member 
                        <br>Since</br></span>
                        <span class="detail-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card activity-card">
                <div class="card-header">
                    <h2>
                        <span class="card-icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        Recent Activity
                    </h2>
                </div>
                
                <div class="activity-list" aria-label="Recent activity log">
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
                            <i class="fas fa-inbox" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 10px;" aria-hidden="true"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon" aria-hidden="true">
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
                                        <i class="far fa-calendar-alt" style="margin-right: 4px;" aria-hidden="true"></i>
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

            <div class="dashboard-card items-card full-width">
                <div class="card-header">
                    <h2>
                        <span class="card-icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                        Items I've Reported
                        <span class="item-count">(<?= $total_items ?>)</span>
                    </h2>
                    <div class="card-filters">
                        <select class="filter-select" onchange="filterItems(this.value)" aria-label="Filter reported items by status">
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
                                        <td><i class="far fa-calendar-alt" style="margin-right: 4px; color: #64748b;" aria-hidden="true"></i><?= date('M d, Y', strtotime($item['found_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= h($item['status']) ?>">
                                                <?= ucfirst(h($item['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="view-item.php?id=<?= $item['id'] ?>" class="btn-action view-btn" aria-label="View details for <?= h($item['title']) ?>">
                                                <i class="fas fa-eye" aria-hidden="true"></i> <span class="action-text">View</span>
                                            </a>
                                            <?php if ($item['status'] === 'recent'): ?>
                                                <a href="edit-item.php?id=<?= $item['id'] ?>" class="btn-action edit-btn" aria-label="Edit <?= h($item['title']) ?>">
                                                    <i class="fas fa-edit" aria-hidden="true"></i> <span class="action-text">Edit</span>
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
                        <div class="empty-icon" aria-hidden="true"><i class="fas fa-inbox"></i></div>
                        <h3>No items reported yet</h3>
                        <p>When you find and report lost items, they'll appear here.</p>
                        <a href="turn-in-item.php" class="btn btn-primary">Report Your First Item</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card claims-card full-width">
                <div class="card-header">
                    <h2>
                        <span class="card-icon" aria-hidden="true"><i class="fas fa-hand-holding"></i></span>
                        My Claim Requests
                        <span class="item-count">(<?= $total_requests ?>)</span>
                    </h2>
                </div>
                
                <?php if (count($my_requests) > 0): ?>
                    <div class="claims-grid">
                        <?php foreach ($my_requests as $request): ?>
                            <div class="claim-card">
                                <div class="claim-header">
                                    <h3><?= h($request['item_title']) ?></h3>
                                    <span class="status-badge status-<?= h($request['status']) ?>">
                                        <?= ucfirst(h($request['status'])) ?>
                                    </span>
                                </div>
                                
                                <div class="claim-details">
                                    <div class="claim-date">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #64748b;" aria-hidden="true"></i>
                                        <strong>Submitted:</strong> <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($request['proof_description'])): ?>
                                        <div class="claim-proof">
                                            <strong><i class="fas fa-file-alt" style="margin-right: 4px;" aria-hidden="true"></i> My Proof:</strong>
                                            <p><?= h(substr($request['proof_description'], 0, 150)) ?>...</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="claim-footer">
                                    <a href="view-item.php?id=<?= $request['item_id'] ?>" class="btn btn-secondary btn-small" aria-label="View details for <?= h($request['item_title']) ?>">
                                        <i class="fas fa-eye" aria-hidden="true"></i> View Item
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state compact">
                        <i class="fas fa-search" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 10px;" aria-hidden="true"></i>
                        <p>You haven't submitted any claim requests yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card claims-card full-width">
                <div class="card-header">
                    <h2>
                        <span class="card-icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                        Claims on My Turned In Items
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
                                        <i class="fas fa-user" style="margin-right: 6px; color: #64748b;" aria-hidden="true"></i>
                                        <strong>Claimant:</strong> <?= h($claim['claimer_name'] ?? 'Anonymous') ?>
                                    </div>
                                    <div class="claim-date">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #64748b;" aria-hidden="true"></i>
                                        <strong>Date:</strong> <?= date('M d, Y', strtotime($claim['created_at'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($claim['proof_description'])): ?>
                                        <div class="claim-proof">
                                            <strong><i class="fas fa-file-alt" style="margin-right: 4px;" aria-hidden="true"></i> Proof provided:</strong>
                                            <p><?= h(substr($claim['proof_description'], 0, 150)) ?>...</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="claim-footer">
                                    <a href="view-item.php?id=<?= $claim['item_id'] ?>" class="btn btn-secondary btn-small" aria-label="View item <?= h($claim['item_title']) ?>">
                                        <i class="fas fa-eye" aria-hidden="true"></i> View Item
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state compact">
                        <i class="fas fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 10px;" aria-hidden="true"></i>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>