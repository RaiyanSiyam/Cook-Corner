<?php
// admin/header.php - FULL & COMPLETE CODE
// This file now contains all necessary code to create a unified header for the admin panel.

// Ensure session is started and user is an authenticated admin.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CookCorner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .nav-link:hover { color: #3b82f6; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Main Website Header (Included for consistent UI) -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-6">
                
                <a href="../homepage.php" class="flex items-center space-x-2">
                    <span class="text-2xl font-bold text-gray-800">Cook<span class="text-pink-400">Corner</span></span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-6 text-lg">
                    <a href="index.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Dashboard</a>
                    <a href="manage_users.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Users</a>
                    <a href="manage_products.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Products</a>
                    <a href="manage_recipes.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Recipes</a>
                    <a href="manage_orders.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Orders</a>
                    <a href="manage_posts.php" class="nav-link font-semibold text-gray-600 hover:text-blue-600 transition duration-300">Blog</a>
                </nav>

                <!-- Auth Links & User Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2">
                                <i data-feather="user"></i>
                                <span>Account</span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                 <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                     <a href="../homepage.php" class="block px-5 py-2 text-blue-600 font-bold hover:bg-blue-50">User View</a>
                                 <?php endif; ?>
                                 <a href="../logout.php" class="block px-5 py-2 text-gray-700 hover:bg-gray-100 "><strong>Logout</strong></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    
    <!-- This opens the main content area for the specific admin page -->
    <main class="py-10">
        <div class="container mx-auto px-4">

