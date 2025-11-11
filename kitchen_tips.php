<?php
// kitchen_tips.php
// This page provides a filterable list of cooking tips and a FAQ section.

include 'header.php';
?>

<style>
    /* Custom styles for the filter buttons and tip cards */
    .tip-filter-btn {
        transition: all 0.2s ease-in-out;
    }
    .tip-filter-btn.active {
        background-color: #3b82f6; /* brand-blue */
        color: white;
        box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39);
    }
    .tip-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    /* Style for the FAQ accordion */
    .faq-item summary {
        cursor: pointer;
        outline: none;
    }
    .faq-item[open] summary .chev {
        transform: rotate(180deg);
    }
</style>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <!-- Page Header -->
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Kitchen Tips & Tricks</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">Sharpen your skills and cook like a pro with these essential tips from our kitchen to yours.</p>
        </div>

        <!-- Filter Buttons -->
        <div id="tipFilters" class="flex flex-wrap justify-center gap-2 mb-12" data-aos="fade-up">
            <button class="tip-filter-btn active px-4 py-2 text-sm font-semibold rounded-full bg-white hover:bg-gray-100" data-filter="all">All Tips</button>
            <button class="tip-filter-btn px-4 py-2 text-sm font-semibold rounded-full bg-white hover:bg-gray-100" data-filter="prep">Prep</button>
            <button class="tip-filter-btn px-4 py-2 text-sm font-semibold rounded-full bg-white hover:bg-gray-100" data-filter="technique">Technique</button>
            <button class="tip-filter-btn px-4 py-2 text-sm font-semibold rounded-full bg-white hover:bg-gray-100" data-filter="flavor">Flavor</button>
            <button class="tip-filter-btn px-4 py-2 text-sm font-semibold rounded-full bg-white hover:bg-gray-100" data-filter="storage">Storage</button>
        </div>

        <!-- Tips Grid -->
        <div id="tipsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Tip 1 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="prep" data-aos="fade-up">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full">Prep</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Mise en Place</h3>
                    <p class="mt-2 text-gray-600">"Everything in its place." Prep all your ingredients (chop veggies, measure spices) before you start cooking. This makes the cooking process smooth and stress-free.</p>
                </div>
            </div>
            <!-- Tip 2 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="technique" data-aos="fade-up" data-aos-delay="50">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-pink-600 bg-pink-100 rounded-full">Technique</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Don't Crowd the Pan</h3>
                    <p class="mt-2 text-gray-600">Give your ingredients space in the pan. Overcrowding steams food instead of searing it, preventing that delicious golden-brown crust.</p>
                </div>
            </div>
            <!-- Tip 3 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="flavor" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-100 rounded-full">Flavor</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Salt Your Pasta Water</h3>
                    <p class="mt-2 text-gray-600">It's the only chance you have to season the pasta itself. Make the water "as salty as the sea" for perfectly seasoned pasta every time.</p>
                </div>
            </div>
            <!-- Tip 4 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="technique" data-aos="fade-up">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-pink-600 bg-pink-100 rounded-full">Technique</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Rest Your Meat</h3>
                    <p class="mt-2 text-gray-600">Let cooked meat (steaks, roasts) rest for 5-10 minutes before slicing. This allows the juices to redistribute, ensuring a tender, flavorful result.</p>
                </div>
            </div>
            <!-- Tip 5 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="storage" data-aos="fade-up" data-aos-delay="50">
                <div class="p-6">
                     <span class="inline-block px-3 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded-full">Storage</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Herb Hotel</h3>
                    <p class="mt-2 text-gray-600">Keep delicate herbs like parsley and cilantro fresh by placing them in a jar with water (like flowers), covering with a bag, and refrigerating.</p>
                </div>
            </div>
            <!-- Tip 6 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="flavor" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-100 rounded-full">Flavor</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Finish with Acid</h3>
                    <p class="mt-2 text-gray-600">A squeeze of lemon juice or a splash of vinegar at the end of cooking can brighten up a dish and make all the flavors pop. It's a secret weapon for soups and sauces.</p>
                </div>
            </div>
            <!-- Tip 7 -->
             <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="prep" data-aos="fade-up">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full">Prep</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Sharpen Your Knives</h3>
                    <p class="mt-2 text-gray-600">A sharp knife is safer and more efficient than a dull one. It cuts cleanly without slipping, giving you better control and precision.</p>
                </div>
            </div>
             <!-- Tip 8 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="flavor" data-aos="fade-up" data-aos-delay="50">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-100 rounded-full">Flavor</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Toast Your Spices</h3>
                    <p class="mt-2 text-gray-600">Briefly toasting whole spices in a dry pan over medium heat before grinding them awakens their essential oils and deepens their flavor dramatically.</p>
                </div>
            </div>
             <!-- Tip 9 -->
            <div class="tip-card transition duration-300 ease-in-out rounded-lg overflow-hidden shadow-lg bg-white" data-category="technique" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-pink-600 bg-pink-100 rounded-full">Technique</span>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">Control Your Heat</h3>
                    <p class="mt-2 text-gray-600">Don't be afraid to adjust the heat throughout the cooking process. A hot pan is great for searing, but you may need to lower it to cook ingredients through without burning.</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-20 max-w-3xl mx-auto" data-aos="fade-up">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Frequently Asked Questions</h2>
            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <details class="faq-item bg-white p-6 rounded-lg shadow-sm">
                    <summary class="font-semibold text-lg flex justify-between items-center">
                        How do I stop pasta from sticking together?
                        <i data-feather="chevron-down" class="chev transition-transform duration-300"></i>
                    </summary>
                    <p class="mt-4 text-gray-600">Use a large pot with plenty of salted boiling water. Stir the pasta immediately after you add it and a few times as it cooks. Rinsing after draining is only necessary for cold pasta salads.</p>
                </details>
                <!-- FAQ Item 2 -->
                <details class="faq-item bg-white p-6 rounded-lg shadow-sm">
                    <summary class="font-semibold text-lg flex justify-between items-center">
                        Can I substitute fresh herbs for dried?
                        <i data-feather="chevron-down" class="chev transition-transform duration-300"></i>
                    </summary>
                    <p class="mt-4 text-gray-600">Yes! The general rule is to use three times the amount of fresh herbs as dried (e.g., 1 tablespoon of fresh oregano for 1 teaspoon of dried). Add fresh herbs towards the end of cooking for the best flavor.</p>
                </details>
                <!-- FAQ Item 3 -->
                <details class="faq-item bg-white p-6 rounded-lg shadow-sm">
                    <summary class="font-semibold text-lg flex justify-between items-center">
                        How do I know when oil is hot enough for frying?
                        <i data-feather="chevron-down" class="chev transition-transform duration-300"></i>
                    </summary>
                    <p class="mt-4 text-gray-600">The oil will shimmer slightly just before it starts to smoke. You can also drop a small piece of bread into the oil; if it sizzles and turns golden-brown in about 30-60 seconds, the oil is ready.</p>
                </details>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.tip-filter-btn');
        const tipCards = document.querySelectorAll('.tip-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active button style
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');

                // Show/hide cards based on category
                tipCards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-category') === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php
include 'footer.php';
?>
