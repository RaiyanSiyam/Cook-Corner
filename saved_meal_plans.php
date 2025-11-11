<?php
// saved_meal_plans.php (Upgraded with Custom Delete Modal)

// DEFINITIVE FIX: Include the database connection *before* the header to prevent crash.
require_once __DIR__ . '/db.php';
include 'header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-center py-20'><p class='text-lg'>Please <a href='login.html' class='text-blue-600 font-bold'>login</a> to view your saved meal plans.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all saved meal plans for the current user
try {
    $sql = "SELECT * FROM user_meal_plans WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $saved_plans = $stmt->fetchAll();
} catch (PDOException $e) {
    $saved_plans = [];
    error_log("DB Error fetching saved meal plans: " . $e->getMessage());
}
?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">My Saved Meal Plans</h1>
            <p class="mt-4 text-lg text-gray-600">Your collection of weekly meal inspirations.</p>
        </div>

        <div class="space-y-8">
            <?php if (empty($saved_plans)): ?>
                <div class="text-center py-16 bg-white rounded-lg shadow">
                    <p class="text-gray-600 text-lg">You haven't saved any meal plans yet.</p>
                    <a href="meal_plan.php" class="mt-4 inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-full">Generate a New Plan</a>
                </div>
            <?php else: ?>
                <?php foreach ($saved_plans as $plan): 
                    // Safely decode the JSON data for the plan
                    $plan_data = json_decode($plan['plan_data'], true);
                    if (!is_array($plan_data)) { continue; } // Skip if data is invalid
                    $saved_date = date("F j, Y", strtotime($plan['created_at']));
                ?>
                <div class="bg-white rounded-lg shadow-lg" data-aos="fade-up">
                    <!-- Header for each saved plan -->
                    <div class="p-4 bg-gray-50 rounded-t-lg border-b flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($plan['plan_name']) ?></h2>
                            <p class="text-sm text-gray-500">Saved on <?= $saved_date ?></p>
                        </div>
                        <!-- IMPORTANT: The form is still here, but the button is now a standard button -->
                        <form id="delete-form-<?= $plan['id'] ?>" action="delete_meal_plan.php" method="POST" class="hidden">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        </form>
                        <button type="button" class="delete-btn text-red-500 hover:text-red-700 transition" title="Delete Plan" data-plan-id="<?= $plan['id'] ?>">
                            <i data-feather="trash-2"></i>
                        </button>
                    </div>
                    <!-- Displaying each day of the saved plan -->
                    <div class="space-y-4 p-4">
                        <?php foreach ($plan_data as $day => $meals): ?>
                        <div class="border rounded-lg p-4">
                             <h3 class="text-xl font-bold text-gray-700 mb-2"><?= htmlspecialchars($day) ?></h3>
                             <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <?php foreach ($meals as $meal_name => $recipe): ?>
                                <div class="flex items-start">
                                    <?php if($recipe): ?>
                                    <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="flex-shrink-0">
                                        <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-20 h-20 object-cover rounded-md mr-4">
                                    </a>
                                    <div>
                                        <p class="font-bold text-blue-600 text-sm"><?= htmlspecialchars($meal_name) ?></p>
                                        <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="font-semibold text-gray-900 hover:underline leading-tight"><?= htmlspecialchars($recipe['title']) ?></a>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-gray-400 text-sm flex items-center h-full">No recipe was saved for this slot.</div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- NEW: Custom Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden transition-opacity duration-300 opacity-0">
    <div id="modal-panel" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all scale-95 opacity-0">
        <div class="text-center">
            <i data-feather="alert-triangle" class="w-12 h-12 mx-auto text-red-500"></i>
            <h3 class="text-2xl font-bold text-gray-800 mt-4">Delete Meal Plan?</h3>
            <p class="text-gray-600 mt-2">This action cannot be undone. Are you sure you want to permanently delete this meal plan?</p>
        </div>
        <div class="mt-8 flex justify-center space-x-4">
            <button id="cancel-delete-btn" class="bg-gray-200 text-gray-800 font-bold py-2 px-6 rounded-md hover:bg-gray-300 transition">
                Cancel
            </button>
            <button id="confirm-delete-btn" class="bg-red-600 text-white font-bold py-2 px-6 rounded-md hover:bg-red-700 transition">
                Confirm Delete
            </button>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-modal');
    const modalPanel = document.getElementById('modal-panel');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const deleteBtns = document.querySelectorAll('.delete-btn');
    let planIdToDelete = null;

    function showModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalPanel.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    function hideModal() {
        modalPanel.classList.add('scale-95', 'opacity-0');
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    deleteBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            planIdToDelete = btn.dataset.planId;
            showModal();
        });
    });

    cancelBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            hideModal();
        }
    });

    confirmBtn.addEventListener('click', () => {
        if (planIdToDelete) {
            const form = document.getElementById(`delete-form-${planIdToDelete}`);
            if (form) {
                form.submit();
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>

