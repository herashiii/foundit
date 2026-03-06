<?php 
include __DIR__ . '/../includes/header.php';
 ?>
<link rel="stylesheet" href="../css/faqs.css">

<!-- HERO SECTION -->
<section class="faq-hero">
    <div class="container">

        <div class="breadcrumb">
            <a href="../Pages/index.php">Home</a> / <span>FAQs</span>
        </div>

        <h1 class="faq-title">Frequently Asked Questions</h1>

        <p class="faq-subtitle">
            Everything you need to know about using the Lost & Found registry.
        </p>

    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section">
<div class="container">

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
Yes. Hazardous objects, illegal materials, and dangerous items should not be submitted through the system. 
These should be reported directly to campus security or the appropriate university office for proper handling.
</div>
</div>

<div class="faq-item">
<div class="faq-question">
How are valuable items handled in the system?
<span class="faq-icon">▼</span>
</div>

<div class="faq-answer">
High-value items such as smartphones, laptops, wallets, or electronic devices may require additional verification 
before they can be released. This helps ensure that valuable items are returned to their rightful owners.
</div>
</div>


<div class="faq-item">
<div class="faq-question">
Can someone else claim an item on my behalf?
<span class="faq-icon">▼</span>
</div>

<div class="faq-answer">
Yes, but the person claiming the item must provide proper authorization from the original owner and present 
valid identification. Additional verification may also be required before the item can be released.
</div>
</div>


<div class="faq-item">
<div class="faq-question">
What if I see multiple items that look similar to mine?
<span class="faq-icon">▼</span>
</div>

<div class="faq-answer">
If several items appear similar to yours, carefully review the descriptions and photos in each listing. 
If you are still unsure, you may submit a claim request and provide detailed identifying information 
to help administrators verify the correct item.
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


<script>

const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(item => {

item.querySelector(".faq-question").addEventListener("click", () => {

item.classList.toggle("active");

});

});

</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>