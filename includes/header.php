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

        #menu-toggle {
            display: none;
        }

        .menu-icon {
            display: none;
            cursor: pointer;
            padding: 5px 0 5px 10px;
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
            }

            .nav-links {
                display: none; 
                flex-direction: column;
                position: absolute;
                top: 100%;
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
            position: relative;
        }

        .a11y-btn:hover {
            transform: scale(1.1);
            background: #742A2A;
        }

        .a11y-btn:focus-visible {
            outline: 3px solid yellow;
            outline-offset: 2px;
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

        /* Text-to-Speech Controls */
        .tts-controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #9B2C2C;
            color: white;
            border-radius: 50px;
            padding: 15px 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            min-width: 320px;
            border: 2px solid white;
        }

        .tts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .tts-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .tts-close-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .tts-progress {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .tts-progress-bar {
            height: 100%;
            background: white;
            transition: width 0.3s ease;
        }

        .tts-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tts-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
        }

        .tts-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .tts-btn:focus-visible {
            outline: 2px solid white;
            outline-offset: 2px;
        }

        .tts-speed {
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            margin-left: auto;
            font-size: 12px;
            cursor: pointer;
        }

        .tts-speed option {
            background: #9B2C2C;
            color: white;
        }

        .tts-status {
            font-size: 12px;
            text-align: center;
            opacity: 0.9;
        }

        .tts-highlight {
            background-color: rgba(255, 255, 0, 0.2);
            transition: background-color 0.3s;
            border-radius: 4px;
            padding: 2px 0;
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

            .tts-controls {
                width: 90%;
                min-width: auto;
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Font Size Indicator -->
    <div id="fontSizeIndicator" class="font-size-indicator" aria-live="polite"></div>

    <!-- Text-to-Speech Control Panel (initially hidden) -->
    <div id="ttsControls" class="tts-controls" style="display: none;" role="region" aria-label="Text to speech controls">
        <div class="tts-header">
            <span><i class="fas fa-volume-up"></i> Reading Aloud</span>
            <button class="tts-close-btn" aria-label="Stop and close">×</button>
        </div>
        <div class="tts-progress">
            <div id="ttsProgressBar" class="tts-progress-bar" style="width: 0%;"></div>
        </div>
        <div class="tts-buttons">
            <button id="ttsPauseBtn" class="tts-btn" aria-label="Pause reading" title="Pause">
                <i class="fas fa-pause"></i>
            </button>
            <button id="ttsResumeBtn" class="tts-btn" style="display: none;" aria-label="Resume reading" title="Resume">
                <i class="fas fa-play"></i>
            </button>
            <button class="tts-btn" onclick="stopTTS()" aria-label="Stop reading" title="Stop">
                <i class="fas fa-stop"></i>
            </button>
            <select class="tts-speed" aria-label="Reading speed">
                <option value="0.8">Slow</option>
                <option value="1" selected>Normal</option>
                <option value="1.2">Fast</option>
                <option value="1.5">Very Fast</option>
            </select>
        </div>
        <div class="tts-status" id="ttsStatus">Ready to read</div>
    </div>

    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-container">
            <a href="../Pages/index.php" class="logo" aria-label="FoundiT Home">
                <img src="../favicon.png" alt="FoundiT Logo" class="logo-img" style="width: 24px; height: 24px; margin-right: 8px;">
                <div>Found<span>iT</span></div>
            </a>

            <input type="checkbox" id="menu-toggle">
            
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

            <!-- Text-to-Speech Button -->
            <button class="a11y-btn" onclick="toggleTextToSpeech()" title="Text to Speech - Read page aloud" aria-label="Text to Speech - Read page aloud" id="ttsButton">
                <i class="fas fa-volume-up"></i>
            </button>
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

    <!-- Text-to-Speech Script -->
<script>
    // Text-to-Speech variables
    let ttsUtterance = null;
    let ttsIsPlaying = false;
    let ttsIsPaused = false;
    let ttsCurrentElement = null;
    let ttsElements = [];
    let ttsCurrentIndex = 0;
    let ttsSpeed = 1; // Default speed
    
    // Toggle Text-to-Speech
    function toggleTextToSpeech() {
        const ttsControls = document.getElementById('ttsControls');
        
        if (ttsControls.style.display === 'none' || ttsControls.style.display === '') {
            ttsControls.style.display = 'flex';
            // Small delay to ensure DOM is ready
            setTimeout(() => prepareTTS(), 100);
        } else {
            forceStopTTS();
            ttsControls.style.display = 'none';
        }
    }
    
    // Force stop TTS completely
    function forceStopTTS() {
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        ttsIsPlaying = false;
        ttsIsPaused = false;
        
        if (ttsCurrentElement) {
            ttsCurrentElement.classList.remove('tts-highlight');
            ttsCurrentElement = null;
        }
        
        // Reset UI
        const pauseBtn = document.getElementById('ttsPauseBtn');
        const resumeBtn = document.getElementById('ttsResumeBtn');
        const progressBar = document.getElementById('ttsProgressBar');
        
        if (pauseBtn) pauseBtn.style.display = 'inline-flex';
        if (resumeBtn) resumeBtn.style.display = 'none';
        if (progressBar) progressBar.style.width = '0%';
        
        updateTTSStatus('Stopped');
    }
    
    // Stop and close
    function stopTTS() {
        forceStopTTS();
        document.getElementById('ttsControls').style.display = 'none';
    }
    
    // Prepare content for TTS
    function prepareTTS() {
        // Stop any ongoing speech
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        
        // Get all readable content
        const mainContent = document.querySelector('main') || document.body;
        
        // Clear previous elements
        ttsElements = [];
        
        // Get all text elements
        const textElements = mainContent.querySelectorAll('h1, h2, h3, h4, h5, h6, p, li, .alert, .card, .panel, label, .btn, a, .description, .doc-text');
        
        textElements.forEach(el => {
            // Skip hidden elements and very short text
            const text = el.innerText.trim();
            if (el.offsetParent !== null && text.length > 15 && !el.classList.contains('sr-only')) {
                ttsElements.push(el);
            }
        });
        
        // Also get the page title
        const pageTitle = document.querySelector('h1');
        if (pageTitle && pageTitle.innerText.trim()) {
            ttsElements.unshift(pageTitle);
        }
        
        if (ttsElements.length > 0) {
            ttsCurrentIndex = 0;
            updateTTSStatus(`Ready to read ${ttsElements.length} sections. Click Play to start.`);
            
            // Reset UI
            document.getElementById('ttsPauseBtn').style.display = 'inline-flex';
            document.getElementById('ttsResumeBtn').style.display = 'none';
            document.getElementById('ttsProgressBar').style.width = '0%';
        } else {
            updateTTSStatus('No readable content found');
        }
    }
    
    // Start reading from beginning
    function startReading() {
        if (ttsElements.length === 0) {
            prepareTTS();
            return;
        }
        
        ttsCurrentIndex = 0;
        ttsIsPaused = false;
        readCurrentSection();
    }
    
    // Read the current section
    function readCurrentSection() {
        if (ttsCurrentIndex >= ttsElements.length) {
            finishTTS();
            return;
        }
        
        const element = ttsElements[ttsCurrentIndex];
        const text = element.innerText.trim();
        
        if (!text) {
            // Skip empty elements
            ttsCurrentIndex++;
            setTimeout(() => readCurrentSection(), 100);
            return;
        }
        
        // Scroll to element and highlight it
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('tts-highlight');
        
        // Remove highlight from previous element
        if (ttsCurrentElement && ttsCurrentElement !== element) {
            ttsCurrentElement.classList.remove('tts-highlight');
        }
        
        ttsCurrentElement = element;
        
        // Create utterance
        ttsUtterance = new SpeechSynthesisUtterance(text);
        ttsUtterance.rate = ttsSpeed;
        ttsUtterance.pitch = 1;
        ttsUtterance.volume = 1;
        
        // Get available voices
        const voices = window.speechSynthesis.getVoices();
        
        // Try to find a good voice
        const preferredVoice = voices.find(v => 
            v.lang.includes('en') && (v.name.includes('Google UK') || 
            v.name.includes('Samantha') || 
            v.name.includes('Microsoft') ||
            v.name.includes('Daniel'))
        );
        
        if (preferredVoice) {
            ttsUtterance.voice = preferredVoice;
        }
        
        // Event handlers
        ttsUtterance.onstart = function() {
            ttsIsPlaying = true;
            ttsIsPaused = false;
            updateTTSStatus(`Reading section ${ttsCurrentIndex + 1} of ${ttsElements.length}`);
            updateProgressBar();
            document.getElementById('ttsPauseBtn').style.display = 'inline-flex';
            document.getElementById('ttsResumeBtn').style.display = 'none';
        };
        
        ttsUtterance.onend = function() {
            // Remove highlight
            if (element) {
                element.classList.remove('tts-highlight');
            }
            
            // Move to next section
            ttsCurrentIndex++;
            
            // Small delay before next section
            setTimeout(() => {
                if (ttsCurrentIndex < ttsElements.length) {
                    readCurrentSection();
                } else {
                    finishTTS();
                }
            }, 300);
        };
        
        ttsUtterance.onerror = function(event) {
            console.error('TTS error:', event);
            if (event.error === 'interrupted' || event.error === 'canceled') {
                // Normal interruption, don't show error
                return;
            }
            updateTTSStatus('Error reading section');
            
            // Try next section
            ttsCurrentIndex++;
            setTimeout(() => readCurrentSection(), 500);
        };
        
        // Cancel any ongoing speech and start new
        try {
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(ttsUtterance);
        } catch (e) {
            console.error('Speech synthesis error:', e);
            updateTTSStatus('Speech not supported');
        }
    }
    
    // Pause reading
    function pauseTTS() {
        if (window.speechSynthesis && ttsIsPlaying && !ttsIsPaused) {
            window.speechSynthesis.pause();
            ttsIsPaused = true;
            updateTTSStatus('Paused');
            document.getElementById('ttsPauseBtn').style.display = 'none';
            document.getElementById('ttsResumeBtn').style.display = 'inline-flex';
        }
    }
    
    // Resume reading
    function resumeTTS() {
        if (window.speechSynthesis && ttsIsPaused) {
            window.speechSynthesis.resume();
            ttsIsPaused = false;
            updateTTSStatus(`Reading section ${ttsCurrentIndex + 1} of ${ttsElements.length}`);
            document.getElementById('ttsPauseBtn').style.display = 'inline-flex';
            document.getElementById('ttsResumeBtn').style.display = 'none';
        } else if (!ttsIsPlaying && ttsElements.length > 0) {
            // Start from beginning if not playing
            startReading();
        }
    }
    
    // Stop reading
    function stopTTS() {
        forceStopTTS();
    }
    
    // Finish TTS
    function finishTTS() {
        updateTTSStatus('Finished reading');
        if (ttsCurrentElement) {
            ttsCurrentElement.classList.remove('tts-highlight');
        }
        forceStopTTS();
    }
    
    // Change reading speed
    function changeTTSRate(rate) {
        ttsSpeed = parseFloat(rate);
        
        if (ttsIsPlaying && !ttsIsPaused && ttsUtterance) {
            // Restart current section with new speed
            const wasPlaying = true;
            const currentIdx = ttsCurrentIndex;
            
            // Cancel current speech
            window.speechSynthesis.cancel();
            ttsIsPlaying = false;
            
            // Restart from same section
            setTimeout(() => {
                ttsCurrentIndex = currentIdx;
                readCurrentSection();
            }, 100);
        } else if (ttsIsPaused) {
            // If paused, just update the speed for when it resumes
            // We'll restart from current section when resumed
            const currentIdx = ttsCurrentIndex;
            window.speechSynthesis.cancel();
            ttsIsPlaying = false;
            ttsIsPaused = false;
            
            setTimeout(() => {
                ttsCurrentIndex = currentIdx;
                readCurrentSection();
            }, 100);
        }
    }
    
    // Update progress bar
    function updateProgressBar() {
        if (ttsElements.length > 0) {
            const progress = ((ttsCurrentIndex) / ttsElements.length) * 100;
            document.getElementById('ttsProgressBar').style.width = progress + '%';
        }
    }
    
    // Update status message
    function updateTTSStatus(message) {
        const statusEl = document.getElementById('ttsStatus');
        if (statusEl) {
            statusEl.textContent = message;
        }
    }
    
    // Load voices when available
    if (window.speechSynthesis) {
        // Load voices immediately if available
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            console.log('Voices loaded:', voices.length);
        }
        
        // Listen for voices to load
        window.speechSynthesis.onvoiceschanged = function() {
            console.log('Voices loaded:', window.speechSynthesis.getVoices().length);
        };
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
    });
    
    // Add click handlers for play/pause/stop buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Override the toggle function to properly handle play/stop
        const originalToggle = toggleTextToSpeech;
        window.toggleTextToSpeech = function() {
            const ttsControls = document.getElementById('ttsControls');
            
            if (ttsControls.style.display === 'none' || ttsControls.style.display === '') {
                ttsControls.style.display = 'flex';
                setTimeout(() => {
                    prepareTTS();
                    // Auto-start after preparation
                    setTimeout(() => {
                        if (ttsElements.length > 0) {
                            startReading();
                        }
                    }, 500);
                }, 100);
            } else {
                stopTTS();
                ttsControls.style.display = 'none';
            }
        };
        
        // Add direct handlers for play/pause/stop
        const pauseBtn = document.getElementById('ttsPauseBtn');
        const resumeBtn = document.getElementById('ttsResumeBtn');
        const stopBtn = document.querySelector('.tts-close-btn');
        
        if (pauseBtn) {
            pauseBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                pauseTTS();
            };
        }
        
        if (resumeBtn) {
            resumeBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                resumeTTS();
            };
        }
        
        if (stopBtn) {
            stopBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                stopTTS();
            };
        }
        
        // Fix speed selector
        const speedSelect = document.querySelector('.tts-speed');
        if (speedSelect) {
            speedSelect.onchange = function(e) {
                e.preventDefault();
                changeTTSRate(this.value);
            };
        }
    });
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
    
</body>
</html>