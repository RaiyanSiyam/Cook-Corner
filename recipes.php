<?php
// recipes.php (Definitive Fix for Crash and Missing Button)

// DEFINITIVE FIX: The database must be connected first, before the header is included.
require_once __DIR__ . '/db.php';
include 'header.php';

// --- Pagination Setup ---
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Filtering and Sorting Logic ---
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest'; 

// Determine the ORDER BY clause
$order_by_sql = "ORDER BY MIN(r.id) DESC"; // Default: newest
if ($sort_by === 'rating') {
    $order_by_sql = "ORDER BY MAX(r.average_rating) DESC, MAX(r.rating_count) DESC";
}

$where_clauses = [];
$params = [];
if ($search_term) {
    $where_clauses[] = "r.title LIKE ?";
    $params[] = '%' . $search_term . '%';
}
if ($category_id > 0) {
    $where_clauses[] = "r.category_id = ?";
    $params[] = $category_id;
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Fetch Categories for the dropdown ---
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log("DB error fetching categories: " . $e->getMessage());
}

// --- Fetch Total Number of Recipes ---
try {
    $count_sql = "SELECT COUNT(DISTINCT r.title) FROM recipes r $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_recipes = $count_stmt->fetchColumn();
    $total_pages = ceil($total_recipes / $limit);
} catch (PDOException $e) { 
    $total_recipes = 0;
    $total_pages = 1;
}

// --- Fetch Recipes for the current page ---
try {
    $sql = "SELECT MIN(r.id) as id, r.title, r.image_url, r.prep_time_minutes, r.cook_time_minutes, c.name as category_name, MAX(r.average_rating) as average_rating, MAX(r.rating_count) as rating_count
            FROM recipes r
            LEFT JOIN categories c ON r.category_id = c.id
            $where_sql
            GROUP BY r.title
            $order_by_sql
            LIMIT ? OFFSET ?";
    
    $params_with_pagination = array_merge($params, [$limit, $offset]);
    $stmt = $pdo->prepare($sql);
    foreach($params_with_pagination as $key => $val){
        $stmt->bindValue($key + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) { 
    $recipes = [];
    error_log("DB error fetching recipes: " . $e->getMessage());
}
?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-gray-800">Discover Recipes</h1>
        </div>

        <!-- Modern Filter Bar -->
        <form action="recipes.php" method="GET" class="bg-white p-6 rounded-lg shadow-md mb-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
                <!-- Search Input -->
                <div class="sm:col-span-2">
                    <label for="q" class="block text-sm font-medium text-gray-700">Search Recipe</label>
                    <div class="relative mt-1">
                        <input type="text" name="q" id="q" placeholder="e.g., Chicken Parmesan" value="<?= htmlspecialchars($search_term) ?>" class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <i data-feather="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                </div>
                <!-- Sort By Dropdown -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
                    <select name="sort" id="sort" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="rating" <?= $sort_by == 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                    </select>
                </div>
                <!-- Category Dropdown -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Action Buttons -->
                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700">Apply</button>
                    <a href="recipes.php" class="w-full text-center bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-md hover:bg-gray-300">Clear</a>
                </div>
            </div>
            <!-- DEFINITIVE FIX: Add Recipe Button RESTORED -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="mt-4 pt-4 border-t text-center">
                 <a href="add_recipe.php" class="inline-flex items-center bg-green-100 text-green-800 font-bold py-2 px-4 rounded-full hover:bg-green-200 transition">
                    <i data-feather="plus" class="w-5 h-5 mr-2"></i> Add Your Own Recipe
                </a>
            </div>
            <?php endif; ?>
        </form>

        <!-- Recipe Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php if (empty($recipes)): ?>
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-600 text-lg">No recipes found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="recipe-card bg-white rounded-lg overflow-hidden shadow-lg transition duration-300">
                        <a href="recipe_details.php?id=<?= $recipe['id'] ?>">
                            <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-full h-48 object-cover">
                        </a>
                        <div class="p-5">
                            <span class="text-xs font-semibold text-blue-600 uppercase"><?= htmlspecialchars($recipe['category_name']) ?></span>
                            <h3 class="mt-2 text-lg font-bold text-gray-800 truncate">
                                <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($recipe['title']) ?></a>
                            </h3>
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i data-feather="clock" class="w-4 h-4 mr-1"></i>
                                    <?= (int)$recipe['prep_time_minutes'] + (int)$recipe['cook_time_minutes'] ?> min
                                </div>
                                <div class="flex items-center text-sm text-gray-500" title="<?= number_format($recipe['average_rating'], 2) ?> average rating">
                                    <i data-feather="star" class="w-4 h-4 mr-1 text-amber-400 fill-current"></i>
                                    <span class="font-bold"><?= number_format($recipe['average_rating'], 1) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Links -->
        <div class="mt-12 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php 
                $query_params = [];
                if (!empty($search_term)) { $query_params['q'] = $search_term; }
                if ($category_id > 0) { $query_params['category_id'] = $category_id; }
                if (!empty($sort_by)) { $query_params['sort'] = $sort_by; }
                $base_query_string = http_build_query($query_params);
                for ($i = 1; $i <= $total_pages; $i++): 
                ?>
                    <a href="?page=<?= $i ?>&<?= $base_query_string ?>" class="px-4 py-2 border rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>

