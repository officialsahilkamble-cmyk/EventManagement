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

                // Create slug
                $slug = slugify($title);

                // Insert event
                $stmt = $pdo->prepare("INSERT INTO events (title, slug, short_description, full_description, category_id, venue_id, start_datetime, end_datetime, booking_open, booking_close, status, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title,
                    $slug,
                    $short_description,
                    $full_description,
                    $category_id ?: null,
                    $venue_id ?: null,
                    $start_datetime,
                    $end_datetime,
                    $start_datetime, // booking_open
                    $end_datetime,   // booking_close
                    $status,
                    $capacity ?: null,
                    $admin['id']
                ]);

                $event_id = $pdo->lastInsertId();

                // Add seat types if provided
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
                $success = 'Event created successfully!';

                // Redirect after 2 seconds
                header("refresh:2;url=events.php");

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Failed to create event: ' . $e->getMessage();
            }
        }
    }
}

// Get categories and venues
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$venues = $pdo->query("SELECT * FROM venues ORDER BY name")->fetchAll();

$pageTitle = 'Add Event';
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
        <!-- Header -->
        <header class="header flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Add New Event</h2>
                <p class="text-gray-600">Create a new event</p>
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

        <!-- Form Content -->
        <main class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                        <p class="text-red-700 text-sm"><?php echo esc($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mt-0.5 mr-3"></i>
                        <p class="text-green-700 text-sm"><?php echo esc($success); ?> Redirecting...</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="max-w-4xl">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- Basic Information -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-purple-600 mr-3"></i>
                        Basic Information
                    </h3>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Event Title *</label>
                            <input type="text" name="title" required class="input"
                                placeholder="e.g., Summer Music Festival 2025">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                                <select name="category_id" class="input">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo esc($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Venue</label>
                                <select name="venue_id" class="input">
                                    <option value="">Select Venue</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?php echo $venue['id']; ?>"><?php echo esc($venue['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description</label>
                            <textarea name="short_description" rows="2" class="input"
                                placeholder="Brief description (shown in event cards)"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                            <textarea name="full_description" rows="5" class="input"
                                placeholder="Detailed event description"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date & Time
                                    *</label>
                                <input type="datetime-local" name="start_datetime" required class="input">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date & Time *</label>
                                <input type="datetime-local" name="end_datetime" required class="input">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Capacity</label>
                                <input type="number" name="capacity" class="input" placeholder="1000">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" class="input">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ticket Types -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-ticket-alt text-purple-600 mr-3"></i>
                        Ticket Types
                    </h3>

                    <div id="seat-types-container" class="space-y-4">
                        <div class="seat-type-row grid grid-cols-5 gap-4 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Type Name</label>
                                <input type="text" name="seat_types[0][name]" class="input" placeholder="VIP">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Price ($)</label>
                                <input type="number" step="0.01" name="seat_types[0][price]" class="input"
                                    placeholder="100.00">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Quantity</label>
                                <input type="number" name="seat_types[0][quantity]" class="input" placeholder="50">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tax (%)</label>
                                <input type="number" step="0.01" name="seat_types[0][tax_percent]" class="input"
                                    value="10">
                            </div>
                            <div class="flex items-end">
                                <button type="button" onclick="this.closest('.seat-type-row').remove()"
                                    class="btn btn-sm bg-red-500 text-white hover:bg-red-600 w-full">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="addSeatType()" class="btn btn-secondary mt-4">
                        <i class="fas fa-plus mr-2"></i>Add Another Ticket Type
                    </button>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-4">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Create Event
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
    <script>
        let seatTypeIndex = 1;

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
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Price ($)</label>
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
</body>

</html>