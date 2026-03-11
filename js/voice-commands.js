// Voice Command System for FoundiT
// Enables voice navigation and control for accessibility

class VoiceCommandSystem {
    constructor() {
        this.recognition = null;
        this.isListening = false;
        this.commands = [];
        this.feedback = null;
        this.currentPage = this.getCurrentPage();
        this.synthesis = window.speechSynthesis;
        this.voices = [];
        this.speechEnabled = true;
        
        this.init();
    }
    
    init() {
        // Check if browser supports speech recognition
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            // Configure recognition
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            this.recognition.lang = 'en-US';
            this.recognition.maxAlternatives = 1;
            
            // Set up event handlers
            this.recognition.onstart = this.onStart.bind(this);
            this.recognition.onend = this.onEnd.bind(this);
            this.recognition.onresult = this.onResult.bind(this);
            this.recognition.onerror = this.onError.bind(this);
            
            console.log('Voice command system initialized');
        } else {
            console.warn('Speech recognition not supported in this browser');
        }
        
        // Load available voices for speech
        if (this.synthesis) {
            this.synthesis.onvoiceschanged = () => {
                this.voices = this.synthesis.getVoices();
            };
        }
        
        // Define commands based on current page
        this.loadPageCommands();
    }
    
    getCurrentPage() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        return filename || 'index.php';
    }
    
    loadPageCommands() {
        // Base commands available on all pages
        this.commands = [
            { command: 'go home', action: () => this.navigateTo('index.php') },
            { command: 'go to find my item', action: () => this.navigateTo('find-my-item.php') },
            { command: 'find items', action: () => this.navigateTo('find-my-item.php') },
            { command: 'search items', action: () => this.navigateTo('find-my-item.php') },
            { command: 'turn in item', action: () => this.navigateTo('turn-in-item.php') },
            { command: 'report item', action: () => this.navigateTo('turn-in-item.php') },
            { command: 'go to dashboard', action: () => this.navigateTo('dashboard.php') },
            { command: 'my dashboard', action: () => this.navigateTo('dashboard.php') },
            { command: 'go to about', action: () => this.navigateTo('aboutus.php') },
            { command: 'about us', action: () => this.navigateTo('aboutus.php') },
            { command: 'go to faq', action: () => this.navigateTo('faq.php') },
            { command: 'frequently asked questions', action: () => this.navigateTo('faq.php') },
            { command: 'contact us', action: () => this.navigateTo('contactus.php') },
            { command: 'go back', action: () => window.history.back() },
            { command: 'refresh page', action: () => window.location.reload() },
            { command: 'scroll up', action: () => window.scrollTo({ top: 0, behavior: 'smooth' }) },
            { command: 'scroll down', action: () => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }) },
            { command: 'stop listening', action: () => this.stopListening() },
            { command: 'turn off voice', action: () => this.stopListening() },
            { command: 'help', action: () => this.showHelp() },
            { command: 'what can I say', action: () => this.showHelp() },
            { command: 'voice commands', action: () => this.showHelp() }
        ];
        
        // Page-specific commands
        switch(this.currentPage) {
            case 'index.php':
                this.addHomePageCommands();
                break;
            case 'find-my-item.php':
                this.addFindItemPageCommands();
                break;
            case 'turn-in-item.php':
                this.addTurnInItemPageCommands();
                break;
            case 'view-item.php':
                this.addViewItemPageCommands();
                break;
            case 'dashboard.php':
                this.addDashboardPageCommands();
                break;
            case 'admindash.php':
                this.addAdminPageCommands();
                break;
            case 'claim-item.php':
                this.addClaimItemPageCommands();
                break;
        }
    }
    
    addHomePageCommands() {
        this.commands.push(
            { command: 'search for', action: () => this.voiceSearch() },
            { command: 'find', action: () => this.voiceSearch() }
        );
    }
    
    addFindItemPageCommands() {
        this.commands.push(
            { command: 'search', action: () => this.activateSearch() },
            { command: 'search for', action: () => this.activateSearch() },
            { command: 'filter by category', action: () => this.speak('Please select a category from the sidebar') },
            { command: 'show recent', action: () => this.checkFilter('recent') },
            { command: 'show unclaimed', action: () => this.checkFilter('unclaimed') },
            { command: 'sort by newest', action: () => this.selectSort('newest') },
            { command: 'sort by oldest', action: () => this.selectSort('oldest') }
        );
    }
    
    addTurnInItemPageCommands() {
        this.commands.push(
            { command: 'step one', action: () => this.goToStep(1) },
            { command: 'step 1', action: () => this.goToStep(1) },
            { command: 'step two', action: () => this.goToStep(2) },
            { command: 'step 2', action: () => this.goToStep(2) },
            { command: 'step three', action: () => this.goToStep(3) },
            { command: 'step 3', action: () => this.goToStep(3) },
            { command: 'step four', action: () => this.goToStep(4) },
            { command: 'step 4', action: () => this.goToStep(4) },
            { command: 'step five', action: () => this.goToStep(5) },
            { command: 'step 5', action: () => this.goToStep(5) },
            { command: 'next step', action: () => this.clickNext() },
            { command: 'continue', action: () => this.clickNext() },
            { command: 'go back', action: () => this.clickPrev() },
            { command: 'previous step', action: () => this.clickPrev() },
            { command: 'submit report', action: () => this.submitForm('reportForm') },
            { command: 'upload photos', action: () => this.focusElement('photoInput') }
        );
    }
    
    addViewItemPageCommands() {
        this.commands.push(
            { command: 'claim item', action: () => this.clickClaimButton() },
            { command: 'proceed to claim', action: () => this.clickClaimButton() },
            { command: 'view photos', action: () => this.openLightbox() },
            { command: 'next photo', action: () => this.nextPhoto() },
            { command: 'previous photo', action: () => this.prevPhoto() },
            { command: 'close', action: () => this.closeLightbox() }
        );
    }
    
    addDashboardPageCommands() {
        this.commands.push(
            { command: 'my items', action: () => this.scrollToSection('my-items') },
            { command: 'items reported', action: () => this.scrollToSection('my-items') },
            { command: 'my claims', action: () => this.scrollToSection('my-claims') },
            { command: 'claims received', action: () => this.scrollToSection('my-claims') },
            { command: 'profile', action: () => this.scrollToSection('profile') },
            { command: 'account info', action: () => this.scrollToSection('profile') },
            { command: 'filter items', action: () => this.activateFilter() }
        );
    }
    
    addAdminPageCommands() {
        this.commands.push(
            { command: 'show items', action: () => this.switchTab('items') },
            { command: 'items tab', action: () => this.switchTab('items') },
            { command: 'show claims', action: () => this.switchTab('claims') },
            { command: 'claims tab', action: () => this.switchTab('claims') },
            { command: 'show users', action: () => this.switchTab('users') },
            { command: 'users tab', action: () => this.switchTab('users') },
            { command: 'show messages', action: () => this.switchTab('messages') },
            { command: 'messages tab', action: () => this.switchTab('messages') },
            { command: 'pending claims', action: () => this.scrollToPending() }
        );
    }
    
    addClaimItemPageCommands() {
        this.commands.push(
            { command: 'submit claim', action: () => this.submitForm('claimForm') },
            { command: 'submit request', action: () => this.submitForm('claimForm') },
            { command: 'cancel', action: () => this.navigateBack() }
        );
    }
    
    onStart() {
        this.isListening = true;
        this.showFeedback('Listening...', 'listening');
        console.log('Voice recognition started');
    }
    
    onEnd() {
        this.isListening = false;
        this.hideFeedback();
        console.log('Voice recognition ended');
    }
    
    onResult(event) {
        const transcript = Array.from(event.results)
            .map(result => result[0].transcript)
            .join('')
            .toLowerCase()
            .trim();
        
        console.log('Recognized:', transcript);
        this.showFeedback(`You said: "${transcript}"`, 'processing');
        
        // Process the command
        this.processCommand(transcript);
    }
    
    onError(event) {
        console.error('Speech recognition error:', event.error);
        let errorMessage = 'Voice recognition error';
        
        switch(event.error) {
            case 'no-speech':
                errorMessage = 'No speech detected. Please try again.';
                break;
            case 'audio-capture':
                errorMessage = 'No microphone found. Please check your microphone.';
                break;
            case 'not-allowed':
                errorMessage = 'Microphone access denied. Please allow microphone access.';
                break;
            case 'network':
                errorMessage = 'Network error. Please check your connection.';
                break;
        }
        
        this.showFeedback(errorMessage, 'error');
        setTimeout(() => this.hideFeedback(), 3000);
    }
    
    processCommand(transcript) {
        let commandExecuted = false;
        
        // Check for exact matches
        for (let cmd of this.commands) {
            if (transcript.includes(cmd.command)) {
                cmd.action();
                this.speak(`Executing command: ${cmd.command}`);
                this.showFeedback(`Executing: ${cmd.command}`, 'success');
                commandExecuted = true;
                break;
            }
        }
        
        // Check for search queries
        if (!commandExecuted && (transcript.startsWith('search for ') || transcript.startsWith('find '))) {
            const searchTerm = transcript.replace(/^(search for |find )/i, '');
            this.performSearch(searchTerm);
            commandExecuted = true;
        }
        
        // Check for number navigation (e.g., "go to 3" for step 3)
        if (!commandExecuted && transcript.match(/go to (\d+)/i)) {
            const match = transcript.match(/go to (\d+)/i);
            const stepNum = parseInt(match[1]);
            if (stepNum >= 1 && stepNum <= 5) {
                this.goToStep(stepNum);
                commandExecuted = true;
            }
        }
        
        if (!commandExecuted) {
            this.showFeedback('Command not recognized. Say "help" for available commands.', 'error');
            this.speak('Command not recognized. Say help for available commands.');
        }
    }
    
    startListening() {
        if (!this.recognition) {
            alert('Voice recognition is not supported in your browser. Please use Chrome, Edge, or Safari.');
            return;
        }
        
        if (this.isListening) {
            this.stopListening();
            return;
        }
        
        try {
            this.recognition.start();
        } catch (error) {
            console.error('Failed to start recognition:', error);
        }
    }
    
    stopListening() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
        }
    }
    
    showFeedback(message, type = 'info') {
        // Create or update feedback element
        let feedbackEl = document.getElementById('voice-feedback');
        
        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.id = 'voice-feedback';
            feedbackEl.className = 'voice-feedback';
            document.body.appendChild(feedbackEl);
        }
        
        feedbackEl.textContent = message;
        feedbackEl.className = `voice-feedback voice-${type}`;
        feedbackEl.style.display = 'block';
        
        // Auto-hide after 3 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                if (feedbackEl) {
                    feedbackEl.style.display = 'none';
                }
            }, 3000);
        }
    }
    
    hideFeedback() {
        const feedbackEl = document.getElementById('voice-feedback');
        if (feedbackEl) {
            feedbackEl.style.display = 'none';
        }
    }
    
    speak(text) {
        if (!this.speechEnabled || !this.synthesis) return;
        
        // Cancel any ongoing speech
        this.synthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        
        // Use a friendly voice if available
        if (this.voices.length > 0) {
            const preferredVoice = this.voices.find(v => v.name.includes('Google UK') || v.name.includes('Samantha'));
            if (preferredVoice) {
                utterance.voice = preferredVoice;
            }
        }
        
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;
        
        this.synthesis.speak(utterance);
    }
    
    navigateTo(page) {
        window.location.href = page;
    }
    
    navigateBack() {
        window.history.back();
    }
    
    voiceSearch() {
        // Trigger voice search on find-my-item page
        if (this.currentPage === 'find-my-item.php') {
            this.activateSearch();
        } else {
            this.navigateTo('find-my-item.php');
        }
    }
    
    activateSearch() {
        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput) {
            searchInput.focus();
            this.speak('Type your search query or speak now');
            
            // Start listening for search term
            setTimeout(() => this.startListening(), 500);
        }
    }
    
    performSearch(term) {
        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput) {
            searchInput.value = term;
            this.speak(`Searching for ${term}`);
            
            // Submit the search form
            const form = searchInput.closest('form');
            if (form) {
                form.submit();
            }
        }
    }
    
    checkFilter(filterName) {
        const filterCheckbox = document.querySelector(`input[name="f[]"][value="${filterName}"]`);
        if (filterCheckbox) {
            filterCheckbox.checked = true;
            filterCheckbox.closest('form').submit();
            this.speak(`Filtering by ${filterName}`);
        }
    }
    
    selectSort(sortValue) {
        const sortSelect = document.querySelector('select[name="sort"]');
        if (sortSelect) {
            sortSelect.value = sortValue;
            sortSelect.closest('form').submit();
            this.speak(`Sorting by ${sortValue}`);
        }
    }
    
    goToStep(step) {
        const stepBtn = document.querySelector(`.step[data-step="${step}"]`);
        if (stepBtn) {
            stepBtn.click();
            this.speak(`Moving to step ${step}`);
        }
    }
    
    clickNext() {
        const nextBtn = document.querySelector('.nextBtn');
        if (nextBtn) {
            nextBtn.click();
            this.speak('Moving to next step');
        }
    }
    
    clickPrev() {
        const prevBtn = document.querySelector('.prevBtn');
        if (prevBtn) {
            prevBtn.click();
            this.speak('Moving to previous step');
        }
    }
    
    submitForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            // Validate form first
            if (form.checkValidity()) {
                form.submit();
                this.speak('Submitting form');
            } else {
                this.speak('Please complete all required fields');
                form.reportValidity();
            }
        }
    }
    
    focusElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.focus();
            this.speak(`${elementId} is now focused`);
        }
    }
    
    clickClaimButton() {
        const claimBtn = document.querySelector('.btn-primary[href*="claim-item.php"]');
        if (claimBtn) {
            claimBtn.click();
        }
    }
    
    openLightbox() {
        const mainImage = document.getElementById('mainImage');
        if (mainImage) {
            mainImage.click();
            this.speak('Opening photo gallery');
        }
    }
    
    nextPhoto() {
        const nextBtn = document.getElementById('lightboxNext');
        if (nextBtn && nextBtn.style.display !== 'none') {
            nextBtn.click();
        }
    }
    
    prevPhoto() {
        const prevBtn = document.getElementById('lightboxPrev');
        if (prevBtn && prevBtn.style.display !== 'none') {
            prevBtn.click();
        }
    }
    
    closeLightbox() {
        const closeBtn = document.getElementById('lightboxClose');
        if (closeBtn) {
            closeBtn.click();
        }
    }
    
    scrollToSection(sectionClass) {
        const section = document.querySelector(`.${sectionClass}`);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
            this.speak(`Scrolling to ${sectionClass}`);
        }
    }
    
    switchTab(tabName) {
        const tabBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => 
            btn.textContent.toLowerCase().includes(tabName)
        );
        if (tabBtn) {
            tabBtn.click();
            this.speak(`Switching to ${tabName} tab`);
        }
    }
    
    scrollToPending() {
        const pendingSection = document.querySelector('.dashboard-section:has(.pending-row)');
        if (pendingSection) {
            pendingSection.scrollIntoView({ behavior: 'smooth' });
            this.speak('Scrolling to pending claims');
        }
    }
    
    activateFilter() {
        const filterSelect = document.querySelector('.filter-select');
        if (filterSelect) {
            filterSelect.focus();
            this.speak('Select a filter option');
        }
    }
    
    showHelp() {
        // Create help modal
        const helpModal = document.createElement('div');
        helpModal.className = 'voice-help-modal';
        helpModal.innerHTML = `
            <div class="voice-help-content">
                <div class="voice-help-header">
                    <h2><i class="fas fa-microphone-alt"></i> Voice Commands</h2>
                    <button class="voice-help-close" onclick="this.closest('.voice-help-modal').remove()">&times;</button>
                </div>
                <div class="voice-help-body">
                    <div class="command-group">
                        <h3>Navigation</h3>
                        <ul>
                            <li>"go home"</li>
                            <li>"go to find my item"</li>
                            <li>"turn in item"</li>
                            <li>"go to dashboard"</li>
                            <li>"go back"</li>
                            <li>"refresh page"</li>
                            <li>"scroll up / scroll down"</li>
                        </ul>
                    </div>
                    <div class="command-group">
                        <h3>Search</h3>
                        <ul>
                            <li>"search for [item]"</li>
                            <li>"find [item]"</li>
                            <li>"filter by category"</li>
                            <li>"show recent / unclaimed"</li>
                            <li>"sort by newest / oldest"</li>
                        </ul>
                    </div>
                    <div class="command-group">
                        <h3>Turn In Item</h3>
                        <ul>
                            <li>"step [1-5]"</li>
                            <li>"next step / continue"</li>
                            <li>"previous step / go back"</li>
                            <li>"submit report"</li>
                        </ul>
                    </div>
                    <div class="command-group">
                        <h3>View Item</h3>
                        <ul>
                            <li>"claim item"</li>
                            <li>"view photos"</li>
                            <li>"next / previous photo"</li>
                            <li>"close"</li>
                        </ul>
                    </div>
                    <div class="command-group">
                        <h3>General</h3>
                        <ul>
                            <li>"stop listening"</li>
                            <li>"help / voice commands"</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(helpModal);
        this.speak('Showing voice commands help');
    }
}

// Initialize voice command system when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.voiceCommands = new VoiceCommandSystem();
});