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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_venue'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $country = $_POST['country'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $contact_name = $_POST['contact_name'] ?? '';
        $contact_phone = $_POST['contact_phone'] ?? '';

        if (empty($name)) {
            $error = 'Venue name is required';
        } else {
            try {
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE venues SET name = ?, address = ?, city = ?, country = ?, capacity = ?, contact_name = ?, contact_phone = ? WHERE id = ?");
                    $stmt->execute([$name, $address, $city, $country, $capacity ?: null, $contact_name, $contact_phone, $id]);
                    $success = 'Venue updated successfully!';
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO venues (name, address, city, country, capacity, contact_name, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $address, $city, $country, $capacity ?: null, $contact_name, $contact_phone]);
                    $success = 'Venue added successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Failed to save venue: ' . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM venues WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Venue deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Cannot delete venue: ' . $e->getMessage();
    }
}

// Get all venues
$stmt = $pdo->query("SELECT v.*, COUNT(e.id) as event_count FROM venues v LEFT JOIN events e ON v.id = e.venue_id GROUP BY v.id ORDER BY v.name");
$venues = $stmt->fetchAll();

// Get venue for editing
$editVenue = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editVenue = $stmt->fetch();
}

$pageTitle = 'Venues';
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
                <a href="categories.php" class="sidebar-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="venues.php" class="sidebar-link active">
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
                <h2 class="text-2xl font-bold text-gray-800">Venues</h2>
                <p class="text-gray-600">Manage event venues</p>
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

            <!-- Add/Edit Form -->
            <div class="card mb-6 max-w-3xl">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <?php echo $editVenue ? 'Edit Venue' : 'Add New Venue'; ?>
                </h3>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <?php if ($editVenue): ?>
                        <input type="hidden" name="id" value="<?php echo $editVenue['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Venue Name *</label>
                            <input type="text" name="name" required class="input"
                                placeholder="e.g., Madison Square Garden"
                                value="<?php echo esc($editVenue['name'] ?? ''); ?>">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="2" class="input"
                                placeholder="Street address"><?php echo esc($editVenue['address'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                            <input type="text" name="city" class="input" placeholder="New York"
                                value="<?php echo esc($editVenue['city'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Country</label>
                            <input type="text" name="country" class="input" placeholder="USA"
                                value="<?php echo esc($editVenue['country'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Capacity</label>
                            <input type="number" name="capacity" class="input" placeholder="5000"
                                value="<?php echo esc($editVenue['capacity'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Name</label>
                            <input type="text" name="contact_name" class="input" placeholder="John Doe"
                                value="<?php echo esc($editVenue['contact_name'] ?? ''); ?>">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Phone</label>
                            <input type="tel" name="contact_phone" class="input" placeholder="+1 234 567 8900"
                                value="<?php echo esc($editVenue['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="flex gap-2 mt-6">
                        <?php if ($editVenue): ?>
                            <a href="venues.php" class="btn btn-secondary">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit" name="save_venue" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i><?php echo $editVenue ? 'Update Venue' : 'Add Venue'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Venues List -->
            <div class="table-container">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">All Venues (<?php echo count($venues); ?>)</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Contact</th>
                            <th>Events</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $venue): ?>
                            <tr>
                                <td class="font-semibold text-gray-800"><?php echo esc($venue['name']); ?></td>
                                <td>
                                    <div class="text-sm text-gray-600">
                                        <?php echo esc($venue['city']); ?>    <?php echo $venue['city'] && $venue['country'] ? ', ' : ''; ?>    <?php echo esc($venue['country']); ?>
                                    </div>
                                </td>
                                <td><?php echo $venue['capacity'] ? number_format($venue['capacity']) : 'N/A'; ?></td>
                                <td class="text-sm text-gray-600"><?php echo esc($venue['contact_phone'] ?? 'N/A'); ?></td>
                                <td><span class="badge badge-purple"><?php echo $venue['event_count']; ?> events</span></td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="?edit=<?php echo $venue['id']; ?>"
                                            class="text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $venue['id']; ?>"
                                            class="text-red-600 hover:text-red-700"
                                            onclick="return confirm('Delete this venue?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>