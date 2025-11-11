<?php
// admin/manage_recipes.php
// This is the fully upgraded version with live search and dynamic actions.

require_once __DIR__ . '/../db.php';

// --- Build a reliable base URL for local images ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_root = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); 
$base_url = $protocol . $host . $project_root . '/';

// --- Handle Search Functionality ---
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    $sql = "SELECT r.id, r.title, r.cook_time_minutes, u.name AS author_name, c.name AS category_name
            FROM recipes r
            LEFT JOIN users u ON r.author_id = u.id
            LEFT JOIN categories c ON r.category_id = c.id";
    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE r.title LIKE ?";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY r.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    if (isset($_GET['ajax'])) {
        echo '<tr><td colspan="6" class="text-center py-10 text-red-500">Database Error.</td></tr>';
        exit;
    }
    $recipes = [];
    $message = "<div class='text-red-500 text-center p-6'>Error fetching recipes: " . htmlspecialchars($e->getMessage()) . "</div>";
}


// --- Handle AJAX requests for live search ---
if (isset($_GET['ajax'])) {
    if (empty($recipes)) {
        echo '<tr><td colspan="6" class="text-center py-10 text-gray-500">';
        echo !empty($search_term) ? 'No recipes found for "' . htmlspecialchars($search_term) . '".' : 'No recipes found.';
        echo '</td></tr>';
    } else {
        foreach ($recipes as $recipe) {
            echo '<tr>';
            echo '<td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900">' . htmlspecialchars($recipe['id']) . '</td>';
            echo '<td class="py-4 px-4 font-medium text-gray-800">' . htmlspecialchars($recipe['title']) . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . htmlspecialchars($recipe['author_name'] ?? 'N/A') . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . htmlspecialchars($recipe['category_name'] ?? 'N/A') . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . htmlspecialchars($recipe['cook_time_minutes']) . ' mins</td>';
            echo '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium">';
            echo '<a href="edit_recipe.php?id=' . $recipe['id'] . '" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>';
            echo '<a href="delete_recipe.php?id=' . $recipe['id'] . '" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    }
    exit; 
}


include 'header.php';

if (!isset($message)) {
    $message = '';
    if (isset($_GET['updated'])) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Recipe updated successfully!</div>";
    }
    if (isset($_GET['deleted'])) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Recipe permanently deleted.</div>";
    }
}
?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="md:flex justify-between items-center mb-6 space-y-4 md:space-y-0">
        <h1 class="text-2xl font-bold text-gray-800">Manage Recipes</h1>
        
        <form id="search-form" action="manage_recipes.php" method="GET" class="flex-grow md:mx-8">
            <div class="relative">
                <input type="text" id="search-input" name="q" placeholder="Search recipes by title..." value="<?= htmlspecialchars($search_term) ?>" class="w-full pl-10 pr-4 py-2 border rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full">
                     <i data-feather="search" class="text-gray-400"></i>
                </div>
            </div>
        </form>

        <a href="add_recipe.php" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-full hover:bg-blue-700 transition duration-300 whitespace-nowrap">
            + Add New Recipe
        </a>
    </div>

    <?= $message ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cook Time</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="recipe-table-body" class="divide-y divide-gray-200">
                <?php if (empty($recipes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">No recipes found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recipes as $recipe): ?>
                        <tr>
                            <td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($recipe['id']) ?></td>
                            <td class="py-4 px-4 font-medium text-gray-800"><?= htmlspecialchars($recipe['title']) ?></td>
                            <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($recipe['author_name'] ?? 'N/A') ?></td>
                            <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($recipe['category_name'] ?? 'N/A') ?></td>
                            <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($recipe['cook_time_minutes']) ?> mins</td>
                            <td class="py-4 px-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_recipe.php?id=<?= $recipe['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                <a href="delete_recipe.php?id=<?= $recipe['id'] ?>" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto">
        <div class="text-center">
             <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Recipe</h3>
             <p class="text-sm text-gray-500 mt-2">Are you sure? This action is permanent.</p>
        </div>
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
            <a id="confirm-delete-btn" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700">Delete</a>
            <button id="cancel-delete-btn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-modal');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    
    document.getElementById('recipe-table-body').addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-btn')) {
            e.preventDefault();
            confirmBtn.href = e.target.href;
            modal.classList.remove('hidden');
        }
    });

    cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });

    // --- Live Search Logic ---
    const searchInput = document.getElementById('search-input');
    const recipeTableBody = document.getElementById('recipe-table-body');
    let debounceTimer;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const searchTerm = e.target.value;

        debounceTimer = setTimeout(() => {
            recipeTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10">Searching...</td></tr>`;
            
            const url = new URL(window.location);
            url.searchParams.set('q', searchTerm);
            window.history.pushState({}, '', url);

            fetch(`manage_recipes.php?q=${encodeURIComponent(searchTerm)}&ajax=1`)
                .then(response => response.text())
                .then(html => { recipeTableBody.innerHTML = html; })
                .catch(error => {
                    console.error('Search failed:', error);
                    recipeTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-red-500">Error.</td></tr>';
                });
        }, 300);
    });
});
</script>

<?php include 'footer.php'; ?>
