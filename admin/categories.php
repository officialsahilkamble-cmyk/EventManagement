<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();
$error = '';
$success = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $icon = $_POST['icon'] ?? 'fa-tag';

        // Ensure icon has 'fas' or 'far' prefix
        if (!empty($icon) && !str_starts_with($icon, 'fas ') && !str_starts_with($icon, 'far ') && !str_starts_with($icon, 'fab ')) {
            $icon = 'fas ' . $icon;
        }

        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $slug = slugify($name);

            try {
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $icon, $id]);
                    $success = 'Category updated successfully!';
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $slug, $icon]);
                    $success = 'Category added successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Failed to save category: ' . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Category deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Cannot delete category: ' . $e->getMessage();
    }
}

// Get all categories
$stmt = $pdo->query("SELECT c.*, COUNT(e.id) as event_count FROM categories c LEFT JOIN events e ON c.id = e.category_id GROUP BY c.id ORDER BY c.name");
$categories = $stmt->fetchAll();

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

$pageTitle = 'Categories';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME_DISPLAY; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../common/assets/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Sidebar -->
    <aside class="sidebar w-64 fixed left-0 top-0 h-screen overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-8">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
                    <p class="text-xs text-gray-500">Admin Panel</p>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="index.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="events.php" class="sidebar-link">
                    <i class="fas fa-calendar"></i>
                    <span>Events</span>
                </a>
                <a href="bookings.php" class="sidebar-link">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Bookings</span>
                </a>
                <a href="users.php" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="payments.php" class="sidebar-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="reviews.php" class="sidebar-link">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="categories.php" class="sidebar-link active">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="venues.php" class="sidebar-link">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Venues</span>
                </a>
                <a href="reports.php" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="sidebar-link text-red-600">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="ml-64">
        <header class="header flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Categories</h2>
                <p class="text-gray-600">Manage event categories</p>
            </div>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                    <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800"><?php echo esc($admin['full_name']); ?></p>
                    <p class="text-xs text-gray-500">Admin</p>
                </div>
            </div>
        </header>

        <main class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <p class="text-red-700 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo esc($error); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <p class="text-green-700 text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo esc($success); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add/Edit Form -->
                <div class="lg:col-span-1">
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?>
                        </h3>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <?php if ($editCategory): ?>
                                <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                            <?php endif; ?>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category Name
                                        *</label>
                                    <input type="text" name="name" required class="input" placeholder="e.g., Music"
                                        value="<?php echo esc($editCategory['name'] ?? ''); ?>">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Icon (Font
                                        Awesome)</label>
                                    <div class="flex gap-2">
                                        <div
                                            class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <i class="<?php echo esc($editCategory['icon'] ?? 'fa-tag'); ?> text-purple-600 text-xl"
                                                id="icon-preview"></i>
                                        </div>
                                        <input type="text" name="icon" class="input flex-1" placeholder="fa-music"
                                            value="<?php echo esc($editCategory['icon'] ?? 'fa-tag'); ?>"
                                            oninput="document.getElementById('icon-preview').className = this.value + ' text-purple-600 text-xl'">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">e.g., fa-music, fa-futbol, fa-palette</p>
                                </div>

                                <div class="flex gap-2">
                                    <?php if ($editCategory): ?>
                                        <a href="categories.php" class="btn btn-secondary flex-1">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                    <?php endif; ?>
                                    <button type="submit" name="save_category" class="btn btn-primary flex-1">
                                        <i class="fas fa-save mr-2"></i><?php echo $editCategory ? 'Update' : 'Add'; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="lg:col-span-2">
                    <div class="table-container">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">All Categories
                                (<?php echo count($categories); ?>)</h3>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Events</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div
                                                class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <i class="<?php echo esc($category['icon']); ?> text-purple-600"></i>
                                            </div>
                                        </td>
                                        <td class="font-semibold text-gray-800"><?php echo esc($category['name']); ?></td>
                                        <td class="text-sm text-gray-600"><?php echo esc($category['slug']); ?></td>
                                        <td><span class="badge badge-purple"><?php echo $category['event_count']; ?>
                                                events</span></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <a href="?edit=<?php echo $category['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $category['id']; ?>"
                                                    class="text-red-600 hover:text-red-700"
                                                    onclick="return confirm('Delete this category?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>