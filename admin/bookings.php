<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();

// Get all bookings
$stmt = $pdo->query("SELECT b.*, u.full_name, e.title as event_title 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN events e ON b.event_id = e.id 
                     ORDER BY b.created_at DESC");
$bookings = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'active'");
$ticketsSold = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'confirmed'");
$totalEarnings = $stmt->fetch()['revenue'];

$pageTitle = 'Bookings';
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
                <a href="bookings.php" class="sidebar-link active">
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
                <h2 class="text-2xl font-bold text-gray-800">Bookings</h2>
                <p class="text-gray-600">Manage all bookings</p>
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

        <!-- Bookings Content -->
        <main class="p-6">
            <!-- KPI Tiles -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-purple">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Total Bookings</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($totalBookings); ?></h3>
                </div>

                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-pink">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Tickets Sold</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($ticketsSold); ?></h3>
                </div>

                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Total Earnings</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo formatCurrency($totalEarnings); ?></h3>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="table-container">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">All Bookings</h3>
                        <div class="flex gap-3">
                            <button class="btn btn-sm btn-primary">All</button>
                            <button class="btn btn-sm btn-secondary">Confirmed</button>
                            <button class="btn btn-sm btn-secondary">Pending</button>
                            <button class="btn btn-sm btn-secondary">Cancelled</button>
                        </div>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><span class="font-mono text-sm"><?php echo esc($booking['booking_ref']); ?></span></td>
                                <td><?php echo formatDate($booking['created_at'], 'M d, Y'); ?></td>
                                <td><?php echo esc($booking['full_name']); ?></td>
                                <td><?php echo esc($booking['event_title']); ?></td>
                                <td class="font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'confirmed' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'cancelled' => 'badge-error',
                                        'refunded' => 'badge-info'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusClass[$booking['status']] ?? 'badge-info'; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="booking-view.php?id=<?php echo $booking['id']; ?>"
                                        class="text-purple-600 hover:text-purple-700">
                                        <i class="fas fa-eye"></i>
                                    </a>
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