<!-- Footer V3 (Aligned with 14-Page Requirements) -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Col -->
                <div>
                    <div class="logo">
                        <img src="../favicon.png" alt="" class="logo-img">
                        <div>Found<span>iT</span></div>
                    </div>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: #A0AEC0;">
                        The official Silliman University platform for reporting, verifying, and reconnecting students with their lost belongings.
                    </p>
                </div>

                <!-- Core Links Col -->
                <div>
                    <h3 class="footer-title">Platform</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="find-my-item.php">Browse Unclaimed Items</a></li>
                        <li><a href="turn-in-item.php">Turn In Found Item</a></li>
                        <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'guest'): ?>
                            <li><a href="login.php">Student / Staff Login</a></li>
                        <?php else: ?>
                            <li><a href="<?= $_SESSION['user_role'] === 'office' ? 'staff-dashboard.php' : 'dashboard.php' ?>">My Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Info Col -->
                <div>
                    <h3 class="footer-title">Information</h3>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="how-it-works.php">How It Works</a></li>
                        <li><a href="faq.php">Frequently Asked Questions (FAQs)</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="sitemap.php">Site Map</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> FoundiT - Silliman University CCS 8 Project. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>