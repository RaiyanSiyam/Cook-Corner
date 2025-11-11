<?php
// footer.php
// This file contains the footer section and closing HTML tags.
// It should be included at the bottom of every user-facing page.
?>
    <footer class="bg-white border-t border-gray-200">
        <div class="container mx-auto py-12 px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 tracking-wider uppercase">Navigation</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="homepage.php" class="text-base text-gray-500 hover:text-gray-900">Home</a></li>
                        <li><a href="recipes.php" class="text-base text-gray-500 hover:text-gray-900">Recipes</a></li>
                        <li><a href="kitchen_tips.php" class="text-base text-gray-500 hover:text-gray-900">Kitchen Tips</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 tracking-wider uppercase">Tools</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="meal_plan.php" class="text-base text-gray-500 hover:text-gray-900">Meal Planner</a></li>
                        <li><a href="bmi_calculator.php" class="text-base text-gray-500 hover:text-gray-900">BMI Calculator</a></li>
                        <li><a href="occasions.php" class="text-base text-gray-500 hover:text-gray-900">Occasions</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 tracking-wider uppercase">Legal</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="privacy_policy.php" class="text-base text-gray-500 hover:text-gray-900">Privacy Policy</a></li>
                        <li><a href="terms_of_service.php" class="text-base text-gray-500 hover:text-gray-900">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 tracking-wider uppercase">Connect</h3>
                    <div class="mt-4 flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-blue-500"><i data-feather="facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-500"><i data-feather="instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-500"><i data-feather="twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-500"><i data-feather="youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-12 border-t border-gray-200 pt-8">
                <p class="text-base text-gray-400 text-center">&copy; <?= date("Y") ?> Cook Corner. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize AOS (Animate on Scroll)
        AOS.init({
            duration: 700,
            once: true
        });

        // Initialize Feather Icons
        feather.replace();

        // Mobile menu toggle functionality
        const menuBtn = document.getElementById('menuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>

