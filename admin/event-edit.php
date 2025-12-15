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

$event_id = $_GET['id'] ?? 0;

// Get event data
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect('events.php');
}

// Get existing seat types
$stmt = $pdo->prepare("SELECT * FROM seat_types WHERE event_id = ? ORDER BY id");
$stmt->execute([$event_id]);
$seat_types = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $title = $_POST['title'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $venue_id = $_POST['venue_id'] ?? '';
        $short_description = $_POST['short_description'] ?? '';
        $full_description = $_POST['full_description'] ?? '';
        $start_datetime = $_POST['start_datetime'] ?? '';
        $end_datetime = $_POST['end_datetime'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $status = $_POST['status'] ?? 'draft';

        if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
            $error = 'Please fill all required fields';
        } else {
            try {
                $pdo->beginTransaction();

                // Update slug
                $slug = slugify($title);

                // Update event
                $stmt = $pdo->prepare("UPDATE events SET title = ?, slug = ?, short_description = ?, full_description = ?, category_id = ?, venue_id = ?, start_datetime = ?, end_datetime = ?, status = ?, capacity = ? WHERE id = ?");
                $stmt->execute([
                    $title,
                    $slug,
                    $short_description,
                    $full_description,
                    $category_id ?: null,
                    $venue_id ?: null,
                    $start_datetime,
                    $end_datetime,
                    $status,
                    $capacity ?: null,
                    $event_id
                ]);

                // Delete existing seat types
                $stmt = $pdo->prepare("DELETE FROM seat_types WHERE event_id = ?");
                $stmt->execute([$event_id]);

                // Add new seat types
                if (!empty($_POST['seat_types'])) {
                    foreach ($_POST['seat_types'] as $seat) {
                        if (!empty($seat['name']) && !empty($seat['price']) && !empty($seat['quantity'])) {
                            $stmt = $pdo->prepare("INSERT INTO seat_types (event_id, name, price, quantity, tax_percent) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $event_id,
                                $seat['name'],
                                $seat['price'],
                                $seat['quantity'],
                                $seat['tax_percent'] ?? 10
                            ]);
                        }
                    }
                }

                $pdo->commit();
                $success = 'Event updated successfully!';

                // Reload event data
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch();

                $stmt = $pdo->prepare("SELECT * FROM seat_types WHERE event_id = ? ORDER BY id");
                $stmt->execute([$event_id]);
                $seat_types = $stmt->fetchAll();

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Failed to update event: ' . $e->getMessage();
            }
        }
    }
}

// Get categories and venues
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$venues = $pdo->query("SELECT * FROM venues ORDER BY name")->fetchAll();

$pageTitle = 'Edit Event';
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
    <link rel="stylesheet" href="../common/assets/css/image-uploader.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Sidebar (same as event-add.php) -->
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
                <a href="events.php" class="sidebar-link active">
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
                <h2 class="text-2xl font-bold text-gray-800">Edit Event</h2>
                <p class="text-gray-600">Update event details</p>
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

            <form method="POST" class="max-w-4xl">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-purple-600 mr-3"></i>
                        Basic Information
                    </h3>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Event Title *</label>
                            <input type="text" name="title" required class="input"
                                value="<?php echo esc($event['title']); ?>">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                                <select name="category_id" class="input">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $event['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo esc($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Venue</label>
                                <select name="venue_id" class="input">
                                    <option value="">Select Venue</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?php echo $venue['id']; ?>" <?php echo $event['venue_id'] == $venue['id'] ? 'selected' : ''; ?>>
                                            <?php echo esc($venue['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description</label>
                            <textarea name="short_description" rows="2"
                                class="input"><?php echo esc($event['short_description']); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                            <textarea name="full_description" rows="5"
                                class="input"><?php echo esc($event['full_description']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date & Time
                                    *</label>
                                <input type="datetime-local" name="start_datetime" required class="input"
                                    value="<?php echo date('Y-m-d\TH:i', strtotime($event['start_datetime'])); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date & Time *</label>
                                <input type="datetime-local" name="end_datetime" required class="input"
                                    value="<?php echo date('Y-m-d\TH:i', strtotime($event['end_datetime'])); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Capacity</label>
                                <input type="number" name="capacity" class="input"
                                    value="<?php echo esc($event['capacity']); ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" class="input">
                                <option value="draft" <?php echo $event['status'] == 'draft' ? 'selected' : ''; ?>>Draft
                                </option>
                                <option value="active" <?php echo $event['status'] == 'active' ? 'selected' : ''; ?>>
                                    Active</option>
                                <option value="disabled" <?php echo $event['status'] == 'disabled' ? 'selected' : ''; ?>>
                                    Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-ticket-alt text-purple-600 mr-3"></i>
                        Ticket Types
                    </h3>

                    <div id="seat-types-container" class="space-y-4">
                        <?php foreach ($seat_types as $index => $seat): ?>
                            <div class="seat-type-row grid grid-cols-5 gap-4 p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Type Name</label>
                                    <input type="text" name="seat_types[<?php echo $index; ?>][name]" class="input"
                                        value="<?php echo esc($seat['name']); ?>">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Price (₹)</label>
                                    <input type="number" step="0.01" name="seat_types[<?php echo $index; ?>][price]"
                                        class="input" value="<?php echo esc($seat['price']); ?>">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Quantity</label>
                                    <input type="number" name="seat_types[<?php echo $index; ?>][quantity]" class="input"
                                        value="<?php echo esc($seat['quantity']); ?>">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tax (%)</label>
                                    <input type="number" step="0.01" name="seat_types[<?php echo $index; ?>][tax_percent]"
                                        class="input" value="<?php echo esc($seat['tax_percent']); ?>">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" onclick="this.closest('.seat-type-row').remove()"
                                        class="btn btn-sm bg-red-500 text-white hover:bg-red-600 w-full">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" onclick="addSeatType()" class="btn btn-secondary mt-4">
                        <i class="fas fa-plus mr-2"></i>Add Another Ticket Type
                    </button>
                </div>

                <!-- Event Images -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-images text-purple-600 mr-3"></i>
                        Event Images
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Upload images for your event. The first image or marked image will be used as the cover.
                    </p>
                    <div id="eventImageUploader"></div>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Event
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
    <script>
        let seatTypeIndex = <?php echo count($seat_types); ?>;

        function addSeatType() {
            const container = document.getElementById('seat-types-container');
            const newRow = document.createElement('div');
            newRow.className = 'seat-type-row grid grid-cols-5 gap-4 p-4 bg-gray-50 rounded-lg';
            newRow.innerHTML = `
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Type Name</label>
                    <input type="text" name="seat_types[${seatTypeIndex}][name]" class="input" placeholder="Regular">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Price (₹)</label>
                    <input type="number" step="0.01" name="seat_types[${seatTypeIndex}][price]" class="input" placeholder="50.00">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="seat_types[${seatTypeIndex}][quantity]" class="input" placeholder="100">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tax (%)</label>
                    <input type="number" step="0.01" name="seat_types[${seatTypeIndex}][tax_percent]" class="input" value="10">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="this.closest('.seat-type-row').remove()" class="btn btn-sm bg-red-500 text-white hover:bg-red-600 w-full">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            seatTypeIndex++;
        }
    </script>
    
    <script src="../common/assets/js/image-uploader.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        imageUploader = new ImageUploader({
            container: '#eventImageUploader',
            type: 'event',
            entityId: <?php echo $event_id; ?>,
            multiple: true,
            maxFiles: 10
        });
        
        <?php
        $stmt = $pdo->prepare("SELECT id, file_path, is_cover FROM event_gallery WHERE event_id = ? ORDER BY is_cover DESC, id ASC");
        $stmt->execute([$event_id]);
        $existingImages = $stmt->fetchAll();
        if (!empty($existingImages)):
        ?>
        imageUploader.loadExistingImages(<?php echo json_encode(array_map(function($img) {
            return [
                'id' => $img['id'],
                'path' => $img['file_path'],
                'url' => UPLOAD_URL . $img['file_path'],
                'is_cover' => (int)$img['is_cover']
            ];
        }, $existingImages)); ?>);
        <?php endif; ?>
    });
    </script>
</body>

</html>