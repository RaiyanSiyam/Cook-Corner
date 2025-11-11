<?php
// header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
@require_once __DIR__ . '/db.php'; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The title will be set on each individual page -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .nav-link:hover { color: #3b82f6; }
        .gradient-bg { background: linear-gradient(135deg, #f0f9ff 0%, #fff6f6 50%, #fff9f0 100%); }
        .recipe-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .filter-btn { transition: all 0.2s ease-in-out; }
        .filter-btn.active { background-color: #3b82f6; color: white; box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39); }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-8">
            <div class="flex justify-between items-center py-6">
                <!-- Logo -->
                <a href="homepage.php" class="flex items-center space-x-2">
                    <span class="text-2xl font-bold text-gray-800">Cook<span class="text-pink-400">Corner</span></span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6 text-lg">
                    <a href="homepage.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Home</a>
                    <a href="recipes.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Recipes</a>
                    <a href="kitchen_tips.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Kitchen Tips</a>
                    <a href="meal_plan.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Meal Plan</a>
                    <a href="generate_recipe.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Generate Recipe</a>
                    <a href="shop.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Shop</a>
                    <a href="blog.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Blog</a>
                                          
                </div>
                 

                <!-- User/Auth Links for Desktop -->
                <div class="hidden md:flex items-center space-x-6">
                      
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Logged-in User Dropdown -->
                        <div class="relative group pb-3">
                   
                            <button class="flex items-center text-gray-700 font-semibold focus:outline-none">
                                <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
                                <i data-feather="chevron-down" class="w-4 h-4 ml-1"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block transition-all duration-300">
                                <a href="cart.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="shopping-cart" class="w-4 h-4 mr-3"></i> See Cart
                                </a>
                                <a href="my_orders.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="shopping-bag" class="w-4 h-4 mr-3"></i> My Orders
                                </a>
                                <a href="saved_recipes.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="bookmark" class="w-4 h-4 mr-3"></i> Saved Recipes
                                </a>
                                <a href="saved_meal_plans.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="calendar" class="w-4 h-4 mr-3"></i> Saved Meal Plans
                                </a>
                                <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="settings" class="w-4 h-4 mr-3"></i> Settings
                                </a>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <a href="admin/index.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i data-feather="user" class="w-4 h-4 mr-3"></i>Admin Dashboard</a>
                                <?php endif; ?>
                                <div class="border-t my-1"></div>
                                <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i data-feather="log-out" class="w-4 h-4 mr-3"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Logged-out User Buttons -->
                        <a href="login.html" class="font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Login</a>
                        <a href="signup.html" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full transition duration-300">Sign Up</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="menuBtn" class="text-gray-600 hover:text-blue-600 focus:outline-none">
                        <i data-feather="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="homepage.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Home</a>
                <a href="recipes.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Recipes</a>
                <a href="kitchen_tips.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Kitchen Tips</a>
                <a href="meal_plan.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Meal Plan</a>
                <a href="generate_recipe.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Generate Recipe</a>
                <a href="shop.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Shop</a>
                <!-- Mobile Auth Links -->
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                         
                         <a href="saved_recipes.php" class="block px-5 py-2 text-gray-700 hover:bg-gray-100">Saved Recipes</a>
                         <a href="saved_meal_plans.php" class="block px-5 py-2 text-gray-700 hover:bg-gray-100">Saved Meal Plans</a>
                         <a href="settings.php" class="block px-5 py-2 text-gray-700 hover:bg-gray-100">Settings</a>
                         <a href="logout.php" class="block px-5 py-2 text-gray-700 hover:bg-gray-100 mt-2 border-t"><strong>Logout</strong></a>
                    <?php else: ?>
                        <a href="login.html" class="block w-full text-left px-5 py-2 text-gray-700 hover:bg-gray-100 text-center">Login</a>
                        <a href="signup.html" class="block w-full text-left mt-2 px-5 py-2 text-white bg-blue-600 rounded-md text-center">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</body>
</html>

