<?php include __DIR__ . '/../includes/header.php'; ?>

<link rel="stylesheet" href="../css/how-it-works.css?v=<?php echo time(); ?>">

<section class="how-section-page">
    <div class="container">

        <div class="breadcrumb">
            <a href="../Pages/index.php">Home</a>
            <span>/</span>
            <a href="../Pages/site-map.php">Site Map</a>
            <span>/</span>
            <span>How It Works</span>
        </div>

        <div class="how-header">
            <h1>How It Works</h1>
            <p>Learn how FoundiT helps reconnect lost items with their rightful owners.</p>
        </div>

        <!-- Video Player Section -->
        <div class="video-placeholder">
            <div class="video-container">
                <video class="video-player" controls>
                    <source src="../video/FoundiT.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>

        <div class="how-block">
            <h2><i class="fas fa-search"></i> Finding a Lost Item</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Search the Registry</h4>
                    <p>Use the Find My Item page to search the list of items that have been turned in across campus.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Review Item Details</h4>
                    <p>Check the item description, location where it was found, and any available photos.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Submit a Claim</h4>
                    <p>If you believe the item is yours, submit a claim request with identifying information.</p>
                </div>
            </div>
        </div>

        <div class="how-block">
            <h2><i class="fas fa-box"></i> Turning In a Found Item</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Report the Item</h4>
                    <p>Click the Turn In Item button and upload photos of the item you found.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Provide Details</h4>
                    <p>Enter information such as where the item was found and the category it belongs to.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Submit the Report</h4>
                    <p>The item will be added to the system so its rightful owner can locate it.</p>
                </div>
            </div>
        </div>

        <div class="how-block">
            <h2><i class="fas fa-check-circle"></i> Claiming an Item</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Submit Claim Request</h4>
                    <p>Provide identifying details about the item to confirm ownership.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Verification</h4>
                    <p>The administrator reviews your claim and verifies the information provided.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Item Release</h4>
                    <p>Once approved, you will receive instructions on how to retrieve your item.</p>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>