<?php
// occasions.php
// This page dynamically displays recipe occasions from the database.

include 'header.php';
require_once __DIR__ . '/db.php';

// Fetch all occasions from the database
try {
    $stmt = $pdo->query("SELECT * FROM occasions ORDER BY name");
    $occasions = $stmt->fetchAll();
} catch (PDOException $e) {
    $occasions = [];
    error_log("DB error fetching occasions: " . $e->getMessage());
}

// Manually assign images to make the page more visually appealing
$occasion_images = [
    'Weeknight Dinner' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=1000',
    'Holiday Feast' => 'https://mariani.com/cdn/shop/articles/Tips_and_Tricks_for_Healthier_Holiday_Feasting.jpg?v=1637164008',
    'Summer BBQ' => 'https://images.unsplash.com/photo-1558030006-450675393462?q=80&w=1000',
    'Party Snacks' => 'https://static01.nyt.com/images/2022/12/07/multimedia/02Dips1-1-bb3d/2Dips1-1-bb3d-superJumbo.jpg'
];

?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4">
        <!-- Page Header -->
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Recipes for Any Occasion</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">From quick weeknight dinners to festive holiday feasts, find the perfect recipe for your next event.</p>
        </div>

        <!-- Occasions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($occasions as $occasion): 
                $image_url = $occasion_images[$occasion['name']] ?? 'https://placehold.co/600x400/f0f9ff/333?text=Recipe';
            ?>
                <a href="recipes.php?occasion_id=<?= $occasion['id'] ?>" class="occasion-card block rounded-lg overflow-hidden shadow-lg relative h-80 group transition duration-300 ease-in-out" data-aos="fade-up">
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($occasion['name']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/50 group-hover:bg-black/60 transition duration-300"></div>
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <h3 class="text-2xl font-bold text-white text-center"><?= htmlspecialchars($occasion['name']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>

