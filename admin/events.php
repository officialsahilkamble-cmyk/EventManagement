<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();

// Get all events with category and venue info
$stmt = $pdo->query("SELECT e.*, c.name as category_name, v.name as venue_name,
                     (SELECT COUNT(*) FROM bookings WHERE event_id = e.id) as booking_count
                     FROM events e 
                     LEFT JOIN categories c ON e.category_id = c.id 
                     LEFT JOIN venues v ON e.venue_id = v.id 
                     ORDER BY e.created_at DESC");
$events = $stmt->fetchAll();

$pageTitle = 'Events';
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
                <h2 class="text-2xl font-bold text-gray-800">Events</h2>
                <p class="text-gray-600">Manage all events</p>
            </div>

            <div class="flex items-center gap-4">
                <a href="event-add.php" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Event
                </a>
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
            </div>
        </header>

        <!-- Events Content -->
        <main class="p-6">
            <div class="table-container">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">All Events (<?php echo count($events); ?>)</h3>
                        <div class="flex gap-3">
                            <input type="text" placeholder="Search events..." class="input input-search">
                            <select class="input">
                                <option>All Status</option>
                                <option>Active</option>
                                <option>Draft</option>
                                <option>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event</th>
                            <th>Category</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><span class="font-mono text-sm">#<?php echo $event['id']; ?></span></td>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo esc($event['title']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo esc(substr($event['short_description'], 0, 50)); ?>...</div>
                                </td>
                                <td><?php echo esc($event['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo esc($event['venue_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($event['start_datetime'], 'M d, Y'); ?></td>
                                <td><span
                                        class="font-semibold text-purple-600"><?php echo $event['booking_count']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'active' => 'badge-success',
                                        'draft' => 'badge-warning',
                                        'disabled' => 'badge-error'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusClass[$event['status']] ?? 'badge-info'; ?>">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="event-edit.php?id=<?php echo $event['id']; ?>"
                                            class="text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="event-view.php?id=<?php echo $event['id']; ?>"
                                            class="text-purple-600 hover:text-purple-700" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?php echo $event['id']; ?>"
                                            class="text-red-600 hover:text-red-700"
                                            onclick="return confirmDelete('Are you sure you want to delete this event?')"
                                            title="Delete">
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