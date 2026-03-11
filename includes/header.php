<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundiT - Campus Lost & Found</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="icon" type="image/x-icon" href="../favicon2.ico">
    <link rel="shortcut icon" href="../favicon2.ico">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/base.css">

    <link rel="stylesheet" href="../css/voice-commands.css">

    <link rel="stylesheet" href="../css/color-blindness.css">

    <?php if(file_exists(__DIR__ . "/../css/" . $current_page . ".css")): ?>
        <link rel="stylesheet" href="../css/<?= htmlspecialchars($current_page) ?>.css">
    <?php endif; ?>

    <style>
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        #menu-toggle, .menu-icon {
            display: none; 
        }

        .nav-actions .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            padding: 8px 20px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-actions .btn-primary {
            background-color: #9B2C2C;
            color: #FFFFFF;
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-primary:hover {
            background-color: #742A2A;
            transform: translateY(-1px);
        }
        
        .nav-actions .btn-secondary {
            background-color: #FFFFFF;
            color: #9B2C2C;
            border: 2px solid #9B2C2C;
        }
        
        .nav-actions .btn-secondary:hover {
            background-color: #FFF5F5;
            transform: translateY(-1px);
        }

        .mobile-only-links {
            display: none;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .menu-icon {
                display: flex;
                flex-direction: column;
                justify-content: center;
                order: 3; 
                cursor: pointer;
                padding: 5px 0 5px 10px; 
                margin: 0;
            }

            .menu-icon span {
                display: block;
                width: 25px;
                height: 3px;
                background: var(--primary);
                margin: 3px 0; 
                border-radius: 2px;
                transition: 0.3s;
            }

            .nav-links {
                display: none; 
                flex-direction: column;
                position: absolute;
                top: var(--header-height);
                left: 0;
                width: 100%;
                background: #fff;
                padding: 20px;
                box-shadow: var(--shadow-md);
                z-index: 1001;
                gap: 15px;
            }

            #menu-toggle:checked ~ .nav-links {
                display: flex;
            }

            .nav-actions {
                display: none; 
            }

            .mobile-only-links {
                display: flex;
                flex-direction: column;
                gap: 12px;
                border-top: 1px solid var(--border);
                margin-top: 10px;
                padding-top: 15px;
            }
        }

        .accessibility-toolbar {
            position: fixed;
            top: 100px;
            right: 10px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .a11y-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #9B2C2C;
            color: white;
            border: 2px solid white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }

        .a11y-btn:hover {
            transform: scale(1.1);
            background: #742A2A;
        }

        .a11y-btn:focus-visible {
            outline: 3px solid yellow;
            outline-offset: 2px;
        }

        .font-size-indicator {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(155, 44, 44, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 9998;
            display: none;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .font-size-indicator.show {
            display: block;
            animation: fadeInOut 2s ease;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }

        @media (max-width: 768px) {
            .accessibility-toolbar {
                top: auto;
                bottom: 100px;
                right: 10px;
                flex-direction: column-reverse;
            }
            
            .a11y-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Font Size Indicator -->
    <div id="fontSizeIndicator" class="font-size-indicator" aria-live="polite"></div>

    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-container">
            <a href="../Pages/index.php" class="logo" aria-label="FoundiT Home">
                <img src="../favicon.png" alt="FoundiT Logo" class="logo-img" style="width: 24px; height: 24px; margin-right: 8px;">
                <div>Found<span>iT</span></div>
            </a>

            <input type="checkbox" id="menu-toggle" style="display: none;">
            
            <div class="nav-links">
                <a href="../Pages/index.php" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">Home</a>
                <a href="../Pages/site-map.php" class="nav-link <?= $current_page == 'site-map' ? 'active' : '' ?>">Site Map</a>
                <a href="../Pages/find-my-item.php" class="nav-link <?= $current_page == 'find-my-item' ? 'active' : '' ?>">Find My Item</a>
                <a href="../Pages/faq.php" class="nav-link <?= $current_page == 'faq' || $current_page == 'faqs' ? 'active' : '' ?>">FAQs</a>
                <a href="../Pages/aboutus.php" class="nav-link <?= $current_page == 'aboutus' ? 'active' : '' ?>">About Us</a>
                
                <div class="mobile-only-links">
                    <?php if ($isLoggedIn): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="../Pages/admindash.php" class="nav-link">Admin Dashboard</a>
                            <a href="#" onclick="confirmLogoutMobile(event)" class="nav-link">Log Out</a>
                        <?php else: ?>
                            <a href="../Pages/dashboard.php" class="nav-link">User Dashboard</a>
                            <a href="#" onclick="confirmLogoutMobile(event)" class="nav-link">Log Out</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../Login/login.php" class="nav-link">Log In</a>
                        <a href="../Pages/turn-in-item.php" class="nav-link" style="color: var(--primary); font-weight: 700;">Turn In Item</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="../Pages/admindash.php" class="btn btn-primary">Admin Dashboard</a>
                        <button onclick="confirmLogout()" class="btn btn-secondary" style="border: 2px solid #9B2C2C; background: white; color: #9B2C2C; cursor: pointer;">Log Out</button>
                    <?php else: ?>
                        <a href="../Pages/dashboard.php" class="btn btn-secondary">User Dashboard</a>
                        <button onclick="confirmLogout()" class="btn btn-primary" style="background: #9B2C2C; color: white; border: 2px solid #9B2C2C; cursor: pointer;">Log Out</button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../Login/login.php" class="btn btn-secondary">Log In</a>
                    <a href="../Pages/turn-in-item.php" class="btn btn-primary">Turn In Item</a>
                <?php endif; ?>
            </div>

            <label for="menu-toggle" class="menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </label>
        </div>

        <!-- Accessibility Toolbar -->
        <div class="accessibility-toolbar" aria-label="Accessibility options">
            <!-- Font Size Controls -->
            <button class="a11y-btn" onclick="increaseFontSize()" title="Increase Font Size (Ctrl++)" aria-label="Increase font size">
                <i class="fas fa-plus-circle"></i>
            </button>
            <button class="a11y-btn" onclick="decreaseFontSize()" title="Decrease Font Size (Ctrl+-)" aria-label="Decrease font size">
                <i class="fas fa-minus-circle"></i>
            </button>
            <button class="a11y-btn" onclick="resetFontSize()" title="Reset Font Size (Ctrl+0)" aria-label="Reset font size">
                <i class="fas fa-undo-alt"></i>
            </button>
            
            <!-- Color Blind Toggle -->
            <button class="a11y-btn" onclick="cycleColorBlindMode()" title="Color Blindness Mode" aria-label="Cycle through color blindness modes" id="colorBlindToggle">
                <i class="fas fa-eye"></i>
                <span class="mode-badge" id="colorBlindIndicator">A</span>
            </button>
        </div>
        </div>
    </nav>

    <!-- Font Size Control Script -->
    <script>
        // Font size control
        let fontSize = parseInt(localStorage.getItem('fontSize')) || 100;
        
        // Apply saved font size on page load
        document.documentElement.style.fontSize = fontSize + '%';
        
        function showFontSizeIndicator(message) {
            const indicator = document.getElementById('fontSizeIndicator');
            indicator.textContent = message;
            indicator.classList.add('show');
            
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 2000);
        }
        
        function increaseFontSize() {
            if (fontSize < 150) {
                fontSize += 10;
                document.documentElement.style.fontSize = fontSize + '%';
                localStorage.setItem('fontSize', fontSize);
                showFontSizeIndicator(`Font size: ${fontSize}%`);
                
                // Announce for screen readers
                const announcer = document.createElement('div');
                announcer.setAttribute('aria-live', 'polite');
                announcer.classList.add('sr-only');
                announcer.textContent = `Font size increased to ${fontSize} percent`;
                document.body.appendChild(announcer);
                setTimeout(() => announcer.remove(), 1000);
            }
        }
        
        function decreaseFontSize() {
            if (fontSize > 70) {
                fontSize -= 10;
                document.documentElement.style.fontSize = fontSize + '%';
                localStorage.setItem('fontSize', fontSize);
                showFontSizeIndicator(`Font size: ${fontSize}%`);
                
                // Announce for screen readers
                const announcer = document.createElement('div');
                announcer.setAttribute('aria-live', 'polite');
                announcer.classList.add('sr-only');
                announcer.textContent = `Font size decreased to ${fontSize} percent`;
                document.body.appendChild(announcer);
                setTimeout(() => announcer.remove(), 1000);
            }
        }
        
        function resetFontSize() {
            fontSize = 100;
            document.documentElement.style.fontSize = fontSize + '%';
            localStorage.setItem('fontSize', fontSize);
            showFontSizeIndicator('Font size reset to 100%');
            
            // Announce for screen readers
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', 'polite');
            announcer.classList.add('sr-only');
            announcer.textContent = 'Font size reset to default';
            document.body.appendChild(announcer);
            setTimeout(() => announcer.remove(), 1000);
        }
        
        // Keyboard shortcuts for font size
        document.addEventListener('keydown', function(e) {
            // Ctrl + Plus (increase)
            if (e.ctrlKey && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                increaseFontSize();
            }
            
            // Ctrl + Minus (decrease)
            if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                decreaseFontSize();
            }
            
            // Ctrl + 0 (reset)
            if (e.ctrlKey && e.key === '0') {
                e.preventDefault();
                resetFontSize();
            }
        });
    </script>

    <!-- Color Blind Mode Script -->
    <script>
    // Color blindness modes
        const colorBlindModes = [
            { id: 'none', name: 'Default', icon: 'A' },
            { id: 'deuteranopia', name: 'Green Blind', icon: 'G' },
            { id: 'protanopia', name: 'Red Blind', icon: 'R' },
            { id: 'tritanopia', name: 'Blue-Yellow Blind', icon: 'B' },
            { id: 'achromatopsia', name: 'Grayscale', icon: 'S' }
        ];
        
        let currentModeIndex = 0;
        
        const savedMode = localStorage.getItem('colorBlindMode');
        if (savedMode) {
            const index = colorBlindModes.findIndex(m => m.id === savedMode);
            if (index !== -1) currentModeIndex = index;
        }
        
        applyColorBlindMode(colorBlindModes[currentModeIndex].id);
        updateColorBlindIndicator();
        
        function cycleColorBlindMode() {
            currentModeIndex = (currentModeIndex + 1) % colorBlindModes.length;
            const mode = colorBlindModes[currentModeIndex];
            
            applyColorBlindMode(mode.id);
            localStorage.setItem('colorBlindMode', mode.id);
            updateColorBlindIndicator();
            
            // Announce for screen readers
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', 'polite');
            announcer.classList.add('sr-only');
            announcer.textContent = mode.name + ' mode activated';
            document.body.appendChild(announcer);
            setTimeout(() => announcer.remove(), 1000);
            
            // Show visual feedback
            showFontSizeIndicator(mode.name + ' mode activated');
        }
        
        function applyColorBlindMode(modeId) {
            // Remove all color blind classes from HTML element (affects entire page)
            document.documentElement.classList.remove(
                'color-blind-deuteranopia',
                'color-blind-protanopia',
                'color-blind-tritanopia',
                'color-blind-achromatopsia'
            );
            
            if (modeId !== 'none') {
                document.documentElement.classList.add(`color-blind-${modeId}`);
            }
        }
        
        function updateColorBlindIndicator() {
            const indicator = document.getElementById('colorBlindIndicator');
            if (indicator) {
                indicator.textContent = colorBlindModes[currentModeIndex].icon;
                
                const button = document.getElementById('colorBlindToggle');
                if (button) {
                    button.setAttribute('title', `Color Blind Mode: ${colorBlindModes[currentModeIndex].name} (Click to cycle)`);
                }
            }
        }
    </script>

    <!-- Logout Confirmation Script -->
    <script>
        // Accessible logout confirmation for desktop
        function confirmLogout() {
            if (confirm('Are you sure you want to sign out? This will end your current session.')) {
                window.location.href = '../Pages/logout.php';
            }
        }

        // Accessible logout confirmation for mobile
        function confirmLogoutMobile(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to sign out? This will end your current session.')) {
                window.location.href = '../Pages/logout.php';
            }
        }

        // keyboard support for logout buttons
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtns = document.querySelectorAll('.nav-actions button[onclick="confirmLogout()"]');
            logoutBtns.forEach(btn => {
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        confirmLogout();
                    }
                });
                
                btn.setAttribute('aria-label', 'Log out of your account');
                btn.setAttribute('role', 'button');
                btn.setAttribute('tabindex', '0');
            });
        });
    </script>

    <!-- Voice Command System -->
    <button id="voiceBtn" class="voice-btn" aria-label="Voice commands" title="Voice commands (Ctrl+Shift+V)">
        <i class="fas fa-microphone"></i>
    </button>

    <script src="../js/voice-commands.js"></script>
    <script>
        // Voice button functionality
        document.addEventListener('DOMContentLoaded', function() {
            const voiceBtn = document.getElementById('voiceBtn');
            
            if (voiceBtn) {
                voiceBtn.addEventListener('click', function() {
                    if (window.voiceCommands) {
                        window.voiceCommands.startListening();
                        this.classList.add('listening');
                        
                        setTimeout(() => {
                            this.classList.remove('listening');
                        }, 5000);
                    }
                });
                
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.shiftKey && e.key === 'V') {
                        e.preventDefault();
                        voiceBtn.click();
                    }
                });
            }
            
            if (navigator.permissions) {
                navigator.permissions.query({ name: 'microphone' }).then(function(permissionStatus) {
                    if (permissionStatus.state === 'denied') {
                        const feedback = document.createElement('div');
                        feedback.className = 'voice-feedback voice-error';
                        feedback.textContent = 'Microphone access is blocked. Please enable microphone for voice commands.';
                        feedback.style.display = 'block';
                        document.body.appendChild(feedback);
                        
                        setTimeout(() => {
                            feedback.remove();
                        }, 5000);
                    }
                });
            }
        });
    </script>

    <style>
        .color-blind-dropdown {
            position: relative;
        }

        .color-blind-menu {
            position: absolute;
            right: 0;
            bottom: 100%;
            margin-bottom: 5px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            padding: 8px 0;
            min-width: 220px;
            z-index: 10000;
        }

        .color-blind-option {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 15px;
            border: none;
            background: none;
            cursor: pointer;
            text-align: left;
            font-size: 14px;
            color: #333;
            transition: background 0.2s;
        }

        .color-blind-option:hover {
            background: #f0f0f0;
        }

        .color-blind-option:focus-visible {
            outline: 2px solid #9B2C2C;
            outline-offset: -2px;
        }

        .color-preview {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .color-preview.normal {
            background: linear-gradient(45deg, #9B2C2C 0%, #D69E2E 100%);
        }

        .color-preview.deuteranopia {
            background: linear-gradient(45deg, #8B4513 0%, #2C5F2D 100%);
        }

        .color-preview.protanopia {
            background: linear-gradient(45deg, #1E3F5A 0%, #8B5A2B 100%);
        }

        .color-preview.tritanopia {
            background: linear-gradient(45deg, #C44536 0%, #4A6D8C 100%);
        }

        .color-preview.achromatopsia {
            background: linear-gradient(45deg, #4A4A4A 0%, #8A8A8A 100%);
        }

        @media (max-width: 768px) {
            .color-blind-menu {
                right: auto;
                left: 0;
                bottom: auto;
                top: 100%;
                margin-top: 5px;
                margin-bottom: 0;
            }
        }

    .mode-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: white;
        color: #9B2C2C;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid #9B2C2C;
    }
    
    #colorBlindToggle {
        position: relative;
    }

        </style>
    
</body>
</html>