<?php 
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="../css/faq.css">

<main class="faq-page">
    <!-- Breadcrumbs & Header -->
    <section class="portal-header-strip">
        <div class="container">
            <nav class="breadcrumb" aria-label="Secondary Navigation">
                <a href="index.php">Home</a>
                <span class="sep" aria-hidden="true">/</span>
                <span class="active">FAQs</span>
            </nav>
            <div class="header-content">
                <h1>Frequently Asked Questions</h1>
                <p>Everything you need to know about using the Lost & Found registry.</p>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="faq-section">
        <div class="container faq-container">

            <div class="faq-item">
                <div class="faq-question">
                    What types of items are commonly reported in the system?
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    Commonly reported items include student IDs, wallets, mobile phones, keys, bags, laptops, notebooks, and personal accessories. 
                    Items that are frequently misplaced on campus are usually the ones most often submitted to the system.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Are there items that cannot be posted in the system?
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    Yes. Hazardous objects, illegal materials, and dangerous items should not be posted. Additionally, very perishable goods or items of extremely low value (like scrap paper) may be discarded directly rather than logged.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    Can I update or correct the information I submitted?
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    If you notice incorrect information in your submission, you may contact the administrator through the Contact Us page. 
                    The administrator can review your request and update the details if necessary.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    What happens to items that remain unclaimed?
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    Items that remain unclaimed after a certain period may be transferred to the university’s lost-and-found office 
                    or handled according to institutional policy. Some items may eventually be donated or disposed of depending on their condition.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    How can I ensure that the item I found is returned to the correct owner?
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    When turning in an item, provide clear photos and accurate information about where the item was found. 
                    This helps administrators record the item properly and assists the rightful owner in identifying it.
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    const faqItems = document.querySelectorAll(".faq-item");
    faqItems.forEach(item => {
        item.querySelector(".faq-question").addEventListener("click", () => {
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove("active");
                }
            });
            item.classList.toggle("active");
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>