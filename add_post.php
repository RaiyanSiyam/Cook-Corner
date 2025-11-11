<?php
// add_post.php
// This page provides a form for users to submit their own blog posts.

include 'header.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($content)) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-lg mb-6'>Title and story content cannot be empty.</div>";
    } else {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/blogs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo(basename($_FILES['image']['name']), PATHINFO_EXTENSION));
            $new_file_name = uniqid('post_', true) . '.' . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest_path)) {
                $image_path = 'uploads/blogs/' . $new_file_name;
            }
        }

        try {
            $sql = "INSERT INTO blog_posts (user_id, title, content, image_url, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $title, $content, $image_path]);
            $message = "<div class='bg-green-100 text-green-700 p-4 rounded-lg mb-6'>Thank you! Your story has been submitted for review.</div>";
        } catch (PDOException $e) {
            $message = "<div class='bg-red-100 text-red-700 p-4 rounded-lg mb-6'>An error occurred. Please try again.</div>";
            error_log($e->getMessage());
        }
    }
}
?>
<main class="py-12 bg-gray-50">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-center mb-6">Share Your Cooking Story</h1>
            <?= $message ?>
            <form action="add_post.php" method="POST" enctype="multipart/form-data">
                <div class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" id="title" name="title" required class="mt-1 w-full p-2 border rounded-md">
                    </div>
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">Your Story</label>
                        <textarea id="content" name="content" rows="10" required class="mt-1 w-full p-2 border rounded-md"></textarea>
                    </div>
                     <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">Upload an Image (Optional)</label>
                        <input type="file" id="image" name="image" accept="image/*" class="mt-1 w-full text-sm text-gray-500 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-md hover:bg-blue-700">Submit for Review</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
