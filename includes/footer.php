<?php
// If current page is inside /Login/, prefix links to go to /Pages/
$currentFolder = basename(dirname($_SERVER['SCRIPT_NAME']));
$pagePrefix = ($currentFolder === 'Login') ? '../Pages/' : '';
?>
    <footer role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="logo">
                        <img src="../favicon.png" alt="" class="logo-img" aria-hidden="true">
                        <div>Found<span>iT</span></div>
                    </div>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: #A0AEC0;">
                        The official Silliman University platform for reporting, verifying, and reconnecting students with their lost belongings.
                    </p>
                </div>

                <div>
                    <h3 class="footer-title">Platform</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $pagePrefix ?>index.php">Home</a></li>
                        <li><a href="<?= $pagePrefix ?>find-my-item.php">Find My Item</a></li>
                        <li><a href="<?= $pagePrefix ?>turn-in-item.php">Turn In Found Item</a></li>

                        <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'guest'): ?>
                            <li>
                            <a href="<?= ($currentFolder === 'Login') ? 'login.php' : '../Login/login.php' ?>">
                                Student / Admin Login
                            </a>
                            </li>
                        <?php else: ?>
                            <li><a href="<?= $pagePrefix . ($_SESSION['user_role'] === 'office' ? 'staff-dashboard.php' : 'dashboard.php') ?>">My Dashboard</a></li>
                        <?php endif; ?>
                        </ul>
                </div>

                <div>
                    <h3 class="footer-title">Information</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $pagePrefix ?>aboutus.php">About Us</a></li>
                        <li><a href="<?= $pagePrefix ?>how-it-works.php">How It Works</a></li>
                        <li><a href="<?= $pagePrefix ?>faq.php">Frequently Asked Questions (FAQs)</a></li>
                        <li><a href="<?= $pagePrefix ?>contactus.php">Contact Us</a></li>
                        <li><a href="<?= $pagePrefix ?>site-map.php">Site Map</a></li>
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