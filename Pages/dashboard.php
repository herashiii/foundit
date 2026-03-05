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
?>

<main class="dashboard-page">
    <div class="container">
        <div class="dashboard-header">
            <h1>My Dashboard</h1>
            <p>Welcome back, <?= h($user['first_name'] . ' ' . $user['last_name']) ?></p>
        </div>

        <div class="dashboard-grid">
            <!-- User Info Card -->
            <div class="dashboard-card user-info">
                <h2>Account Information</h2>
                <div class="info-row">
                    <span class="info-label">Student ID:</span>
                    <span class="info-value"><?= h($user['student_id'] ?? 'Not set') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= h($user['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?= h($user['phone'] ?? 'Not provided') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member since:</span>
                    <span class="info-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                </div>
                
                <div class="dashboard-actions">
                    <button onclick="confirmLogout()" 
                            class="btn btn-logout" 
                            aria-label="Logout from your account"
                            title="Click to securely logout">
                        <span class="btn-icon" aria-hidden="true">🚪</span>
                        <span>Logout</span>
                    </button>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="dashboard-card stats">
                <h2>My Activity</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?= count($my_items) ?></span>
                        <span class="stat-label">Items Reported</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($my_claims) ?></span>
                        <span class="stat-label">Claims Received</span>
                    </div>
                </div>
            </div>

            <!-- My Items Card -->
            <div class="dashboard-card my-items">
                <div class="card-header">
                    <h2>Items I've Reported</h2>
                    <a href="turn-in-item.php" class="btn btn-small btn-primary">+ Report New</a>
                </div>
                
                <?php if (count($my_items) > 0): ?>
                    <div class="items-list">
                        <?php foreach ($my_items as $item): ?>
                            <div class="item-row">
                                <div class="item-info">
                                    <strong><?= h($item['title']) ?></strong>
                                    <span class="item-meta">
                                        <?= h($item['category_name']) ?> • 
                                        Found at <?= h($item['location_name']) ?> • 
                                        <?= date('M d, Y', strtotime($item['found_date'])) ?>
                                    </span>
                                </div>
                                <div class="item-status">
                                    <span class="status-badge status-<?= h($item['status']) ?>">
                                        <?= ucfirst(h($item['status'])) ?>
                                    </span>
                                    <a href="view-item.php?id=<?= $item['id'] ?>" class="btn btn-small btn-secondary">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">You haven't reported any items yet.</p>
                <?php endif; ?>
            </div>

            <!-- Claims Received Card -->
            <div class="dashboard-card my-claims">
                <h2>Claims on My Items</h2>
                
                <?php if (count($my_claims) > 0): ?>
                    <div class="claims-list">
                        <?php foreach ($my_claims as $claim): ?>
                            <div class="claim-row">
                                <div class="claim-info">
                                    <strong>Item: <?= h($claim['item_title']) ?></strong>
                                    <span class="claim-meta">
                                        Claimant: <?= h($claim['claimer_name']) ?> • 
                                        <?= date('M d, Y', strtotime($claim['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="claim-status">
                                    <span class="status-badge status-<?= h($claim['status']) ?>">
                                        <?= ucfirst(h($claim['status'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">No claims received yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        // Accessible logout confirmation
        function confirmLogout() {
            if (confirm('Are you sure you want to logout? This will end your current session.')) {
                window.location.href = 'logout.php';
            }
        }

        // Optional: Add keyboard support (Enter key)
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.querySelector('.btn-logout');
            if (logoutBtn) {
                logoutBtn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        confirmLogout();
                    }
                });
            }
        });
        </script>
</main>

<style>
.dashboard-page {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: #333;
    margin-bottom: 5px;
}

.dashboard-header p {
    color: #666;
    font-size: 16px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.dashboard-card h2 {
    color: #333;
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* User Info Card */
.user-info {
    grid-column: 1;
}

.info-row {
    display: flex;
    margin-bottom: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.info-label {
    width: 120px;
    color: #666;
    font-weight: 500;
}

.info-value {
    flex: 1;
    color: #333;
}

/* Stats Card */
.stats {
    grid-column: 2;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    text-align: center;
}

.stat-item {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: 700;
    color: #9B2C2C;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

/* My Items Card */
.my-items {
    grid-column: span 2;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn-small {
    padding: 6px 12px;
    font-size: 13px;
}

.items-list, .claims-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-row, .claim-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.2s;
}

.item-row:hover, .claim-row:hover {
    transform: translateX(5px);
    background: #f0f2f5;
}

.item-info, .claim-info {
    flex: 1;
}

.item-info strong, .claim-info strong {
    display: block;
    margin-bottom: 4px;
    color: #333;
}

.item-meta, .claim-meta {
    font-size: 13px;
    color: #666;
}

.item-status, .claim-status {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-recent {
    background: #e3f2fd;
    color: #1976d2;
}

.status-pending {
    background: #fff3e0;
    color: #f57c00;
}

.status-claimed {
    background: #e8f5e9;
    color: #388e3c;
}

.status-returned {
    background: #f3e5f5;
    color: #8e24aa;
}

.empty-message {
    text-align: center;
    color: #999;
    padding: 30px;
}

.dashboard-actions {
    margin-top: 20px;
    text-align: right;
}

/* My Claims Card */
.my-claims {
    grid-column: span 2;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .user-info, .stats, .my-items, .my-claims {
        grid-column: 1;
    }
    
    .item-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .item-status {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>